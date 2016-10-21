// Only define com_EasyStaging if it doesn't exist.
/* global cppl_tools */
if (typeof com_EasyStaging === 'undefined')
{
    var com_EasyStaging = {};
}

jQuery( document).ready(function() {
    com_EasyStaging.updateRsyncOptions = '';
    com_EasyStaging.updateRsyncFilexcl = '';
    // We trigger the change so that any pre 2.0 remote sites have their rsync settings cleared out
    jQuery('#jform_type').trigger('change');
});

jQuery(function() {
    "use strict";
    cppl_tools.setUp('com_easystaging');
});

Joomla.submitbutton = function(task) {
    "use strict";
	if (task === 'website.cancel' || document.formvalidator.isValid(document.id('easystaging-form')))
	{
		/* Call Joomla's submit */
		Joomla.submitform(task, document.id('easystaging-form'));
	}
	else
	{
		alert(Joomla.JText._('JGLOBAL_VALIDATION_FORM_FAILED'));
	}
};

com_EasyStaging.updateRsync = function(sel) {
    var rsOpt = jQuery('#jform_rsync_options');
    var fileEx = jQuery('#jform_file_exclusions');

    if (sel.value == 2) {
        jQuery('#es-rsync').hide("slow");
        this.updateRsyncOptions = rsOpt.value;
        rsOpt.val('');
        this.updateRsyncFilexcl = fileEx.value;
        fileEx.val('');
    } else {
        jQuery('#es-rsync').show("slow");
        rsOpt.val(this.updateRsyncOptions);
        fileEx.val(this.updateRsyncFilexcl);
    }
}
