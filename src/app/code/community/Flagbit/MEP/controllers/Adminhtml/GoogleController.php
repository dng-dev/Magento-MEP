<?php

class   Flagbit_MEP_Adminhtml_GoogleController extends Mage_Adminhtml_Controller_Action
{
    public function indexAction()
    {
        $this->loadLayout();
        $this->renderLayout();
    }

    public function loadcategoriesAction()
    {
        $storeId = $this->getRequest()->getParam('store_id');
        if ($storeId)
        {
            Mage::register('category_store_id', $storeId);
        }
        $categoriesTree = Mage::helper('mep/categories')->getCategoriesTree();
        $array = array();
        Mage::helper('mep/categories')->loadCategoryTree($categoriesTree, 1, $array);
        $this->getResponse()->clearHeaders()->setHeader('Content-Type', 'application/json');
        $this->getResponse()->setBody(json_encode($array));
    }

    public function loadtaxonomiesAction()
    {
        $taxonomyId = $this->getRequest()->getParam('taxonomy_id');
        if ($taxonomyId)
        {
            $taxonomies = Mage::helper('mep/categories')->getArrayForTaxonomy($taxonomyId);
            $this->getResponse()->clearHeaders()->setHeader('Content-Type', 'application/json');
            $this->getResponse()->setBody(json_encode($taxonomies));
        }
    }

    public function saveAction()
    {
        if ($data = $this->getRequest()->getPost())
        {
            $mappings = Mage::helper('mep/categories')->prepareMappingForSave($data['google-mapping']);
            try
            {
                foreach ($mappings as $mapping)
                {
                    $newMapping = Mage::getModel('mep/googleMapping');
                    $newMapping->load($mapping['category_id'], 'category_id');
                    $newMapping->setData('category_id', $mapping['category_id']);
                    $newMapping->setData('google_mapping_ids', $mapping['google_mapping_ids']);
                    $newMapping->save();
                }
                Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('mep')->__('Mapping was successfully saved'));
            }
            catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
            }
            $this->_redirect('*/*/');
        }
    }

    public function importcategoriesAction()
    {
        $url = Mage::helper('mep/categories')->getGoogleCategoriesFileUrl();
        /** @var Flagbit_MEP_Model_GoogleMapping_Import $importModel */
        $importModel = Mage::getSingleton('mep/googleMapping_import');
        try
        {
            $importModel->runImportWithUrl($url);
        }
        catch (Exception $e)
        {
            Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
            $this->getResponse()->setHeader('Content-Type', 'application/json');
            $this->getResponse()->setBody(json_encode(array('error' => $e->getMessage())));
        }
    }
}