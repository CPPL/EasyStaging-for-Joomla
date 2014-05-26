<?php
// No direct access to this file
defined('_JEXEC') or die('Restricted Access');
?>
<div>
	<p class="tab-description"><?php echo JText::_('COM_EASYSTAGING_LOCALSITE_TAB_DESC')?></p>
	<fieldset class="form-horizontal span6 localsite">
		<?php
		echo '<h3>' . JText::_('COM_EASYSTAGING_LOCALSITE_TAB') . '</h3>';
		echo $this->form->renderField('site_name', 'localSite');
		echo $this->form->renderField('site_url', 'localSite');
		echo $this->form->renderField('site_path', 'localSite');
		echo $this->form->renderField('take_site_offline', 'localSite'); ?>
	</fieldset>
	<fieldset class="form-horizontal span6 localsite">
		<?php
		echo '<h3>' . JText::_('COM_EASYSTAGING_SETTINGS_DATABASE_ACCESS') . '</h3>';
		echo $this->form->renderField('database_name', 'localSite');
		echo $this->form->renderField('database_user', 'localSite');
		echo $this->form->renderField('database_password', 'localSite');
		echo $this->form->renderField('database_host', 'localSite');
		echo $this->form->renderField('database_table_prefix', 'localSite'); ?>
	</fieldset>
	<fieldset class="form-vertical span12 localsite">
		<?php
		echo '<h3>' . JText::_('COM_EASYSTAGING_SETTINGS_RSYNC_DETAILS') . '</h3>';
		echo $this->form->renderField('rsync_options', 'localSite');
		echo $this->form->renderField('file_exclusions', 'localSite'); ?>
	</fieldset>
</div>
<div class="clr"></div>
