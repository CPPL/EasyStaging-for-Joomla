<?php
// No direct access
defined('_JEXEC') or die('Restricted access');

// Load the tooltip behavior.
JHtml::_('behavior.tooltip');
JHtml::_('behavior.formvalidation');
JHtml::_('behavior.keepalive');

if (!$this->canDo->get('easystaging.run'))
{
	$app =& JFactory::getApplication();
	$app->redirect('index.php?option=com_easystaging');

	return JError::raiseWarning(404, JText::_('COM_EASYSTAGING_RUN_NOAUTH'));
}
?>

<form action="<?php echo JRoute::_('index.php?option=com_easystaging&layout=run&id=' . (int) $this->item->id); ?>"
      method="post" name="adminForm" id="easystaging-form">
	<?php
		// This is ugly
		$this->setLayout('edit');
		echo $this->loadTemplate('plan');
		$this->setLayout('run');
	?>
<!-- Tab UI -->
	<div id="com_easystaging_plan_tabs" class="width-100">
	<?php
		echo JHtml::_('tabs.start', 'com_easystaging_tabs', array('useCookie' => false));

		if ($this->canDo->get('easystaging.run'))
		{
			echo JHtml::_('tabs.panel', JText::_('COM_EASYSTAGING_STATUS_TAB'), 'status');

			// Again this is ugly
			$this->setLayout('edit');
			echo $this->loadTemplate('status');

			// But, it's slightly better than duplicating code :(
			$this->setLayout('run');
		}

		echo JHtml::_('tabs.end');
	?>
	</div>
	<div class="clr"></div>

	<div>
		<input type="hidden" id="id" name="id" value="<?php echo $this->form->getValue('id'); ?>">
		<input type="hidden" name="task" value="plan.run" />
		<?php echo JHtml::_('form.token'); ?>
	</div>
</form>
