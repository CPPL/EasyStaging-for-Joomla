<?php
// No direct access
defined('_JEXEC') or die('Restricted access');
JHtml::_('behavior.tooltip');
// Load the tooltip behavior.
JHtml::_('behavior.tooltip');
JHtml::_('behavior.formvalidation');
JHtml::_('behavior.keepalive');
?>

<form action="<?php echo JRoute::_('index.php?option=com_easystaging&layout=edit&id='.(int) $this->item->id); ?>"
      method="post" name="adminForm" id="easystaging-form">
<!-- Main Form Body -->
	<div class="width-60 fltlft">
		<fieldset class="adminform">
			<legend><?php echo JText::_( 'COM_EASYSTAGING_PLAN_DETAILS' ); ?></legend>
			<ul class="adminformlist">
				<li><?php echo $this->form->getLabel('name'); ?> <?php echo $this->form->getInput('name'); ?></li>
				<li><?php echo $this->form->getLabel('description'); ?> <?php echo $this->form->getInput('description'); ?></li>
				<li><?php echo $this->form->getLabel('published'); ?> <?php echo $this->form->getInput('published'); ?></li>
				<li><?php echo $this->form->getLabel('id'); ?> <?php echo $this->form->getInput('id'); ?></li>

				<li><?php echo $this->form->getLabel('access'); ?>
				<?php echo $this->form->getInput('access'); ?></li>

			<?php if ($this->canDo->get('core.admin')): ?>
				<li><span class="faux-label"><?php echo JText::_('JGLOBAL_ACTION_PERMISSIONS_LABEL'); ?></span>
					<div class="button2-left"><div class="blank">
						<button type="button" onclick="document.location.href='#access-rules';">
							<?php echo JText::_('JGLOBAL_PERMISSIONS_ANCHOR'); ?>
						</button>
					</div></div>
				</li>
			<?php endif; ?>
			</ul>
		</fieldset>
	</div>

<!-- Parameter Sidebar -->
	<div class="width-40 fltrt">
		<?php echo JHtml::_('sliders.start','content-sliders-'.$this->item->id, array('useCookie'=>1)); ?>
		<?php echo JHtml::_('sliders.panel',JText::_('COM_EASYSTAGING_BASIC_ATTRIBUTES_LABEL'), 'basic-options'); ?>
			<fieldset class="panelform">
				<ul class="adminformlist">
					<li><?php echo $this->form->getLabel('created_by'); ?> <?php echo $this->form->getInput('created_by'); ?></li>
	
					<li><?php echo $this->form->getLabel('created'); ?> <?php echo $this->form->getInput('created'); ?></li>
	
					<li><?php echo $this->form->getLabel('publish_up'); ?> <?php echo $this->form->getInput('publish_up'); ?></li>
	
					<li><?php echo $this->form->getLabel('publish_down'); ?> <?php echo $this->form->getInput('publish_down'); ?></li>
	
					<?php if ($this->item->modified_by) : ?>
						<li><?php echo $this->form->getLabel('modified_by'); ?> <?php echo $this->form->getInput('modified_by'); ?></li>
						<li><?php echo $this->form->getLabel('modified'); ?> <?php echo $this->form->getInput('modified'); ?></li>
					<?php endif; ?>
				</ul>
			</fieldset>
		<?php echo JHtml::_('sliders.end'); ?>
	</div>

	<div class="clr"></div>
<!-- Tab UI -->
	<div id="com_easystaging_plan_tabs" class="width-100">
	<?php echo JHtml::_('tabs.start', 'com_easystaging_tabs', array('useCookie'=>true));?> 
		<?php echo JHtml::_('tabs.panel', JText::_('COM_EASYSTAGING_STATUS_TAB'), 'status'); ?><?php echo $this->loadTemplate('status');?>
		<?php echo JHtml::_('tabs.panel', JText::_('COM_EASYSTAGING_LOCALSITE_TAB'), 'local_site'); ?><?php echo $this->loadTemplate('local');?>
		<?php echo JHtml::_('tabs.panel', JText::_('COM_EASYSTAGING_REMOTESITE_TAB'), 'remote_site'); ?><?php echo $this->loadTemplate('remote');?>
		<?php echo JHtml::_('tabs.panel', JText::_('COM_EASYSTAGING_TABLESETTINGS_TAB'), 'table_settings'); ?><?php echo $this->loadTemplate('tables');?>
	<?php echo JHtml::_('tabs.end');?> 
	</div>
	<div class="clr"></div>

<!-- Permissions UI -->
		<div class="width-100 fltlft">
		<div class="clr"></div>
	<?php if ($this->canDo->get('core.admin')): ?>
		<div class="width-100 fltlft">
			<?php echo JHtml::_('sliders.start','permissions-sliders-'.$this->item->id, array('useCookie'=>1)); ?>
				<?php echo JHtml::_('sliders.panel',JText::_('COM_EASYSTAGING_FIELDSET_RULES'), 'access-rules'); ?>
				<fieldset class="panelform">
					<?php echo $this->form->getLabel('rules'); ?>
					<?php echo $this->form->getInput('rules'); ?>
				</fieldset>
			<?php echo JHtml::_('sliders.end'); ?>
		</div>
	<?php endif; ?>

	</div>

	<div>
		<input type="hidden" id="id" name="id" value="<?php echo $this->form->getValue('id'); ?>">
		<input type="hidden" name="task" value="plan.edit" />
		<?php echo JHtml::_('form.token'); ?>
	</div>
</form>
