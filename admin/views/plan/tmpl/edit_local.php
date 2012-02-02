<?php
// No direct access to this file
defined('_JEXEC') or die('Restricted Access');
?>
<p class="tab-description"><?php echo JText::_('COM_EASYSTAGING_LOCALSITE_TAB_DESC')?></p>
	<ul class="adminformlist localsite">
		<li><?php echo $this->form->getLabel('site_name','localSite'); ?> <?php echo $this->form->getInput('site_name','localSite'); ?></li>
		<li><?php echo $this->form->getLabel('site_url','localSite'); ?> <?php echo $this->form->getInput('site_url','localSite'); ?></li>
		<li><?php echo $this->form->getLabel('site_path','localSite'); ?> <?php echo $this->form->getInput('site_path','localSite'); ?></li>
		<li><?php echo $this->form->getLabel('take_site_offline','localSite'); ?> <?php echo $this->form->getInput('take_site_offline','localSite'); ?></li>
		<li><?php echo $this->form->getLabel('database_name','localSite'); ?> <?php echo $this->form->getInput('database_name','localSite'); ?></li>
		<li><?php echo $this->form->getLabel('database_user','localSite'); ?> <?php echo $this->form->getInput('database_user','localSite'); ?></li>
		<li><?php echo $this->form->getLabel('database_password','localSite'); ?> <?php echo $this->form->getInput('database_password','localSite'); ?></li>
		<li><?php echo $this->form->getLabel('database_host','localSite'); ?> <?php echo $this->form->getInput('database_host','localSite'); ?></li>
		<li><?php echo $this->form->getLabel('database_table_prefix','localSite'); ?> <?php echo $this->form->getInput('database_table_prefix','localSite'); ?></li>
		<li><?php echo $this->form->getLabel('rsync_options','localSite'); ?> <?php echo $this->form->getInput('rsync_options','localSite'); ?></li>
		<li><?php echo $this->form->getLabel('file_exclusions','localSite'); ?> <?php echo $this->form->getInput('file_exclusions','localSite'); ?></li>
	</ul>
<div class="clr"></div>
