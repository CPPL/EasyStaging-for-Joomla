<?php
// No direct access to this file
defined('_JEXEC') or die('Restricted Access');
$showRsync = $this->item->type == 1 ? '' : ' style="display: none;"';
?>
<div>
	<fieldset class="form-horizontal span6 website" id="es-website">
		<?php
		echo '<h3>' . JText::_('COM_EASYSTAGING_SITE_TAB') . '</h3>';
		echo $this->form->renderField('type', null);
		echo $this->form->renderField('site_url', null);
		echo $this->form->renderField('site_path', null);
		echo $this->form->renderField('take_site_offline', null); ?>
	</fieldset>
	<fieldset class="form-horizontal span6 website" id="es-database">
		<?php
		echo '<h3>' . JText::_('COM_EASYSTAGING_SETTINGS_DATABASE_ACCESS') . '</h3>';
		echo $this->form->renderField('database_name', null);
		echo $this->form->renderField('database_user', null);
		echo $this->form->renderField('database_password', null);
		echo $this->form->renderField('database_host', null);
		echo $this->form->renderField('database_table_prefix', null); ?>
	</fieldset>
    <fieldset class="form-vertical span12 website" id="es-rsync"<?php echo $showRsync; ?>>
		<?php
		echo '<h3>' . JText::_('COM_EASYSTAGING_SETTINGS_RSYNC_DETAILS') . '</h3>';
		echo $this->form->renderField('rsync_options', null);
		echo $this->form->renderField('file_exclusions', null); ?>
	</fieldset>
</div>
<div class="clr"></div>
