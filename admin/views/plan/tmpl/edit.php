<?php
// No direct access
defined('_JEXEC') or die('Restricted access');

// Load the tooltip behavior.
JHtml::_('behavior.tooltip');
JHtml::_('behavior.formvalidation');
JHtml::_('behavior.keepalive');

if (!$this->canDo->get('core.edit'))
{
	$app =& JFactory::getApplication();
	$app->redirect('index.php?option=com_easystaging');

	return JError::raiseWarning(404, JText::_('JERROR_ALERTNOAUTHOR'));
}
?>

<form action="<?php echo JRoute::_('index.php?option=com_easystaging&layout=edit&id=' . (int) $this->item->id); ?>"
      method="post" name="adminForm" id="easystaging-form">
	<?php echo $this->loadTemplate('plan');?>
<!-- Tab UI -->
	<div id="com_easystaging_plan_tabs" class="width-100">
	<?php echo JHtml::_('tabs.start', 'com_easystaging_tabs', array('useCookie' => false));?>
		<?php
			if ($this->canDo->get('easystaging.run'))
			{
				echo JHtml::_('tabs.panel', JText::_('COM_EASYSTAGING_STATUS_TAB'), 'status');
				echo $this->loadTemplate('status');
			}

			if ($this->canDo->get('core.edit') || ($this->canDo->get('core.create') && ($this->item->id == 0)))
			{
				echo JHtml::_('tabs.panel', JText::_('COM_EASYSTAGING_LOCALSITE_TAB'), 'local_site');
				echo $this->loadTemplate('local');
				echo JHtml::_('tabs.panel', JText::_('COM_EASYSTAGING_REMOTESITE_TAB'), 'remote_site');
				echo $this->loadTemplate('remote');
				echo JHtml::_('tabs.panel', JText::_('COM_EASYSTAGING_TABLESETTINGS_TAB'), 'table_settings');
				echo $this->loadTemplate('tables');
				echo JHtml::_('tabs.panel', JText::_('COM_EASYSTAGING_RSYNCS_TAB'), 'fileCopyActions');
				echo $this->loadTemplate('rsyncs');
			}
		?>
	<?php echo JHtml::_('tabs.end');?> 
	</div>
	<div class="clr"></div>

<!-- Permissions UI -->
	<div class="width-100 fltlft">
		<div class="clr"></div>
	<?php if ($this->canDo->get('core.admin')): ?>
		<div class="width-100 fltlft">
		<?php
			echo JHtml::_('sliders.start', 'permissions-sliders-' . $this->item->id, array('useCookie' => 1));
			echo JHtml::_('sliders.panel', JText::_('COM_EASYSTAGING_FIELDSET_RULES'), 'access-rules'); ?>
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
