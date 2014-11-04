var mepAttributeSettingsDialog = {
    getDivHtml: function(id, html) {
        if (!html) html = '';
        return '<div id="' + id + '">' + html + '</div>';
    },

    onAjaxSuccess: function(transport) {
        if (transport.responseText.isJSON()) {
            var response = transport.responseText.evalJSON()
            if (response.error) {
                throw response;
            } else if (response.ajaxExpired && response.ajaxRedirect) {
                setLocation(response.ajaxRedirect);
            }
        }
    },

    openDialog: function(widgetUrl) {
        if ($('widget_window') && typeof(Windows) != 'undefined') {
            Windows.focus('widget_window');
            return;
        }
        var parameters = {};
        var useTwigTemplatesField = $('use_twig_templates');
        if (useTwigTemplatesField) {
            parameters.use_twig_templates = useTwigTemplatesField.value == 1 ? '1' : '0';
        }

        this.dialogWindow = Dialog.info(null, {
            draggable:false,
            resizable:false,
            closable:true,
            className:'magento',
            windowClassName:"attr-popup-window",
            title:Translator.translate('Attribute Settings'),
            top:100,
            width:400,
            //height:450,
            zIndex:1000,
            recenterAuto:false,
            hideEffect:Element.hide,
            showEffect:Element.show,
            id:'widget_window',
            onClose: this.closeDialog.bind(this)
        });
        new Ajax.Updater('modal_dialog_message', widgetUrl, {
            evalScripts: true,
            parameters: parameters,
            onComplete: function(response) {
                $('widget_window').setStyle({
                    display: 'block'
                });
            }
        });
    },
    closeDialog: function(window) {
        if (!window) {
            window = this.dialogWindow;
        }
        if (window) {
            // IE fix - hidden form select fields after closing dialog
            WindowUtilities._showSelect();
            window.close();
        }
    }
}


var mepPreviewDialog = {
    getDivHtml: function(id, html) {
        if (!html) html = '';
        return '<div id="' + id + '">' + html + '</div>';
    },

    openDialog: function(widgetUrl) {
        if ($('widget_window') && typeof(Windows) != 'undefined') {
            Windows.focus('widget_window');
            return;
        }
        this.dialogWindow = Dialog.info(null, {
            draggable:false,
            resizable:false,
            closable:true,
            className:'magento',
            windowClassName:"popup-window",
            title:Translator.translate('Export Preview'),
            top:0,
            width:100,
            //height:450,
            zIndex:1000,
            recenterAuto:false,
            hideEffect:Element.hide,
            showEffect:Element.show,
            id:'widget_window',
            onClose: this.closeDialog.bind(this)
        });
        new Ajax.Updater('modal_dialog_message', widgetUrl, {
            evalScripts: true,
            onComplete: function(response) {
                var table = new TableKit('preview-table', {
                    editable: false,
                    resizable: false
                });
                $('widget_window').setStyle({
                    display: 'block'
                });
            }
        });
    },
    closeDialog: function(window) {
        if (!window) {
            window = this.dialogWindow;
        }
        if (window) {
            // IE fix - hidden form select fields after closing dialog
            WindowUtilities._showSelect();
            window.close();
        }
    }
}

function toggleApplyVisibility(select) {
    if ($(select).value == 1) {
        $(select).next('select').removeClassName('no-display');
        $(select).next('select').removeClassName('ignore-validate');

    } else {
        $(select).next('select').addClassName('no-display');
        $(select).next('select').addClassName('ignore-validate');
        var options = $(select).next('select').options;
        for( var i=0; i < options.length; i++) {
            options[i].selected = false;
        }
    }
}

function    toggleQtyFilterVisibility(select) {
    if ($(select).value == '') {
        $(select).next('input').addClassName('no-display');
        $(select).next('input').addClassName('ignore-validate');
        $(select).next('input').value = '';
    }
    else {
        $(select).next('input').removeClassName('no-display');
        $(select).next('input').removeClassName('ignore-validate');
    }
}