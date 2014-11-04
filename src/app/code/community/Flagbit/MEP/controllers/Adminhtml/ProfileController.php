<?php

class Flagbit_MEP_Adminhtml_ProfileController extends Mage_Adminhtml_Controller_Action
{
    /**
     * _initAction
     *
     * @return Flagbit_MEP_Adminhtml_ProfileController Self;
     */
    protected function _initAction()
    {
        $this->loadLayout();

        // add support of Magento CE 1.6
        $version_info = Mage::getVersionInfo();
        if ($version_info['major'] == 1 && $version_info['minor'] < 7 && !method_exists('Mage', 'getEdition')) {
            $head = $this->getLayout()->getBlock('head');
            $head->addItem('js_css', 'prototype/windows/themes/magento.css');
            $head->removeItem('skin_css', 'lib/prototype/windows/themes/magento.css');
        }

        $this->_setActiveMenu('mep/profile');
        return $this;
    }

    /**
     * indexAction
     *
     * @return void
     */
    public function indexAction()
    {
        $this->_initAction();
        $this->renderLayout();
    }

    /**
     * indexAction
     *
     * @return void
     */
    public function popupAction()
    {
        $this->loadLayout('empty')->renderLayout();
        $html = $this->getLayout()->createBlock('mep/adminhtml_profile_popup')->setTemplate('mep/popup.phtml')->toHtml();
        $this->getResponse()->setBody($html);
    }

    public function newAction()
    {
        Mage::getSingleton('adminhtml/session')->setMepProfileData(null);
        $this->_initAction();
        $this->renderLayout();
    }

    /**
     * editAction
     *
     * @return void
     */
    public function editAction()
    {
        $id = $this->getRequest()->getParam('id');
        $model = Mage::getModel('mep/profile')->load((int)$id);


        if ($model->getId() || !$id) {
            Mage::register('mep_profil', $model);
            $data = Mage::getSingleton('adminhtml/session')->getFormData(true);
            if ($data) {
                $model->setData($data)->setId($id);
            } else {
                Mage::getSingleton('adminhtml/session')->setMepProfileData($model->getData());
            }

            Mage::register('mep_profile_data', $model);

            $this->_initAction();
            $this->getLayout()->getBlock('head')->setCanLoadExtJs(true);
            $this->_addContent($this->getLayout()->createBlock('mep/adminhtml_profile_view_edit'));
            $this->_addLeft($this->getLayout()->createBlock('mep/adminhtml_profile_view_edit_tabs'));
            $this->renderLayout();
        } else {
            Mage::getSingleton('adminhtml/session')->addError(Mage::helper('mep')->__('Profil does not exist'));
            $this->_redirect('*/*/');
        }
    }

    /**
     * saveAction
     *
     * @return void
     */
    public function saveAction()
    {
        if ($data = $this->getRequest()->getPost()) {
            /** @var Flagbit_MEP_Model_Profile $model */
            $model = Mage::getModel('mep/profile');

            $id = $this->getRequest()->getParam('id');
            $data['id'] = $id;
            if ($id) {
                $model->load($id);
            }
            if (isset($data['rule'])) {

                $data = $this->_filterDates($data, array('from_date', 'to_date'));

                if (isset($data['rule']['conditions'])) {
                    $data['conditions_serialized'] = $data['rule']['conditions'];
                    unset($data['rule']);
                }
            }

            Mage::getSingleton('adminhtml/session')->setFormData($data);

            try {
                $model->setData($data);
                $model->save();

                Mage::getSingleton('adminhtml/session')->setMepProfileData($model->getData());

                if (!$model->getId()) {
                    Mage::throwException(Mage::helper('mep')->__('Error saving Profile'));
                }

                Mage::getSingleton('adminhtml/session')->setFormData(false);

                if($this->getRequest()->getParam('duplicate')) {
                    $newProfile = $model->duplicate();
                    $this->_recalculateProducts($newProfile);
                    Mage::getSingleton('adminhtml/session')->setMepProfileData($newProfile->getData());
                    Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('mep')->__('Profile was successfully cloned'));
                    $this->_redirect('*/*/edit', array('id' => $newProfile->getId(), 'tab' => 'form_section'));

                }elseif ($this->getRequest()->getParam('back')) {
                    $this->_recalculateProducts($model);
                    Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('mep')->__('Profile was successfully saved')
                        . '. ' . Mage::helper('mep')->__('Products count: ') . $model->getProductCount());
                    $this->_redirect('*/*/edit', array('id' => $model->getId(), 'tab' => $this->getRequest()->getParam('tab')));

                } else {
                    $this->_recalculateProducts($model);
                    Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('mep')->__('Profile was successfully saved'));
                    $this->_redirect('*/*/');
                }
            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
                if ($model && $model->getId()) {
                    $this->_redirect('*/*/edit', array('id' => $model->getId()));
                } else {
                    $this->_redirect('*/*/');
                }
            }
            return;
        }
        Mage::getSingleton('adminhtml/session')->addError(Mage::helper('mep')->__('No data found to save'));
        $this->_redirect('*/*/');
    }

    /**
     * Recalculate quantity of products matching the profile and save the value in the database
     *
     * @param Flagbit_MEP_Model_Profile $profile
     *
     * @return void
     */
    private function _recalculateProducts($profile) {
        $export = Mage::getModel('mep/export');
        $export->setData('id', $profile->getId());
        $export->setEntity("catalog_product");
        $export->setExportFilter(array());
        $count = $export->countItems();

        // save value to the database
        $profile->setProductCount($count);
        $profile->save();
    }

    /**
     * deleteAction
     *
     * @return void
     */
    public function deleteAction()
    {
        if ($id = $this->getRequest()->getParam('id')) {
            try {
                Mage::getModel('mep/profile')->load($id)->delete();
                Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('mep')->__('successfully deleted'));
            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
                $this->_redirect('*/*/edit', array('id' => $this->getRequest()->getParam('id')));
            }
        }
        $this->_redirect('*/*/');
    }

    /**
     * massDeleteAction
     *
     * @return void
     */
    public function massDeleteAction()
    {
        $productIds = $this->getRequest()->getParam('product');
        if (!is_array($productIds)) {
            $this->_getSession()->addError($this->__('Please select product(s).'));
        } else {
            try {
                foreach ($productIds as $productId) {
                    Mage::getModel('mep/profile')->load($productId)->delete();
                }
                $this->_getSession()->addSuccess(
                    $this->__('Total of %d profil(s) have been deleted.', count($productIds))
                );
            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
                $this->_redirect('*/*/edit', array('id' => $this->getRequest()->getParam('id')));
            }
        }
        $this->_redirect('*/*/');
    }


    public function previewAction()
    {
        try {

            /* @var $model Flagbit_MEP_Model_Export */
            $model = Mage::getModel('mep/export');
            $model->setData($this->getRequest()->getParams());
            $model->setEntity("catalog_product");
            $model->setFileFormat("twig");
            $model->setExportFilter(array());
            $model->setLimit(20);
            echo Mage::helper('mep/table')->toHtmlTable($model->export(), $this->getRequest()->getParam('id'));
            //echo '<pre>'.htmlspecialchars($model->export()).'</pre>';
            die();
            #return $this->getResponse()->setBody('<pre>'.htmlspecialchars('sss'.$model->export()).'</pre>');

        } catch (Mage_Core_Exception $e) {
            $this->_getSession()->addError($e->getMessage());
            Mage::logException($e);
            echo 'FALSE';
            die();
        } catch (Exception $e) {
            Mage::logException($e);
            $this->_getSession()->addError($this->__('No valid data sent'));
            if(Mage::getIsDeveloperMode()){
                throw $e;
            }
        }
        $this->_redirect('*/*/edit', array('id' => $this->getRequest()->getParam('id')));
    }

    public function runAction()
    {
        try {
            $id = (int) $this->getRequest()->getParam('id');

            $scheduleAheadFor = Mage::getStoreConfig(Mage_Cron_Model_Observer::XML_PATH_SCHEDULE_AHEAD_FOR) * 60;
            $schedule = Mage::getModel('mep/cron');
            $now = time() + 60;
            $timeAhead = $now + $scheduleAheadFor;


            $schedules = Mage::getModel('cron/schedule')->getCollection()
                ->addFieldToFilter('status', Mage_Cron_Model_Schedule::STATUS_PENDING)
                ->load();

            $schedule->setCronExpr('* * * * *')
                ->setStatus(Mage_Cron_Model_Schedule::STATUS_PENDING)
                ->setProfileId($id)
                ->setIgnoreProfileStatus(1)
            ;

            $_errorMsg = null;
            for ($time = $now; $time < $timeAhead; $time += 60) {
                if (!$schedule->trySchedule($time)) {
                    // time does not match cron expression
                    $_errorMsg = Mage::helper('mep')->__('Something went wrong, please try again later.');
                    continue;
                }
                $_errorMsg = null;
                $schedule->unsScheduleId()->save();
                break;
            }
            if($_errorMsg !== NULL){
                $this->_getSession()->addError($_errorMsg);
            }else{
                $this->_getSession()->addSuccess(
                    Mage::helper('mep')->__('Export is scheduled and will run in %s seconds.', $now - time())
                );
            }

        } catch (Mage_Core_Exception $e) {
            $this->_getSession()->addError($e->getMessage());
        } catch (Exception $e) {
            $this->_getSession()->addException($e, Mage::helper('mep')->__('Cannot initialize the export process.'));
        }
        $this->_redirect('*/*/edit', array('id' => $id));

    }


    function generateTemplateAction() {
        echo 'generateTemplateAction called';
        exit;
    }

}
