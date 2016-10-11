<?php
// No direct access to this file
defined('_JEXEC') or die('Restricted Access');
?>
<div>
	<p class="tab-description"><?php echo JText::_('COM_EASYSTAGING_REMOTESITE_TAB_DESC')?></p>
	<fieldset class="form-horizontal span6 remotesite">
		<?php
		echo '<h3>' . JText::_('COM_EASYSTAGING_REMOTESITE_TAB') . '</h3>';
		echo $this->form->renderField('site_name', 'remoteSite');
		echo $this->form->renderField('site_url', 'remoteSite');
		echo $this->form->renderField('site_path', 'remoteSite');
		?>
	</fieldset>
	<fieldset class="form-horizontal span6 remotesite">
		<?php
		echo '<h3>' . JText::_('COM_EASYSTAGING_SETTINGS_DATABASE_ACCESS') . '</h3>';
		echo $this->form->renderField('database_name', 'remoteSite');
		echo $this->form->renderField('database_user', 'remoteSite');
		echo $this->form->renderField('database_password', 'remoteSite');
		echo $this->form->renderField('database_host', 'remoteSite');
		echo $this->form->renderField('database_table_prefix', 'remoteSite');
		?>
	</fieldset>
</div>
