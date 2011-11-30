<?php
// No direct access to this file
defined('_JEXEC') or die('Restricted Access');
?>

<p class="tab-description"><?php echo JText::_('COM_EASYSTAGING_REMOTESITE_TAB_DESC')?></p>
	<ul class="adminformlist remotesite">
		<li><?php echo $this->form->getLabel('site_name','remoteSite'); ?> <?php echo $this->form->getInput('site_name','remoteSite'); ?></li>
		<li><?php echo $this->form->getLabel('site_url','remoteSite'); ?> <?php echo $this->form->getInput('site_url','remoteSite'); ?></li>
		<li><?php echo $this->form->getLabel('site_path','remoteSite'); ?> <?php echo $this->form->getInput('site_path','remoteSite'); ?></li>
		<li><?php echo $this->form->getLabel('take_site_offline','remoteSite'); ?> <?php echo $this->form->getInput('take_site_offline','remoteSite'); ?></li>
		<li><?php echo $this->form->getLabel('database_name','remoteSite'); ?> <?php echo $this->form->getInput('database_name','remoteSite'); ?></li>
		<li><?php echo $this->form->getLabel('database_user','remoteSite'); ?> <?php echo $this->form->getInput('database_user','remoteSite'); ?></li>
		<li><?php echo $this->form->getLabel('database_password','remoteSite'); ?> <?php echo $this->form->getInput('database_password','remoteSite'); ?></li>
		<li><?php echo $this->form->getLabel('database_host','remoteSite'); ?> <?php echo $this->form->getInput('database_host','remoteSite'); ?></li>
		<li><?php echo $this->form->getLabel('database_table_prefix','remoteSite'); ?> <?php echo $this->form->getInput('database_table_prefix','remoteSite'); ?></li>
		<li><?php echo $this->form->getLabel('rsync_options','remoteSite'); ?> <?php echo $this->form->getInput('rsync_options','remoteSite'); ?></li>
		<li><?php echo $this->form->getLabel('file_exclusions','remoteSite'); ?> <?php echo $this->form->getInput('file_exclusions','remoteSite'); ?></li>
	</ul>
<div class="clr"></div>
