<?php
// No direct access
defined('_JEXEC') or die('Restricted access');

// Load the tooltip behavior.
JHtml::_('behavior.formvalidation');
JHtml::_('formbehavior.chosen', 'select');

if (!$this->canDo->get('easystaging.run'))
{
	$app =& JFactory::getApplication();
	$app->redirect('index.php?option=com_easystaging');

	return $app->enqueueMessage(JText::_('COM_EASYSTAGING_RUN_NOAUTH'), 'WARNING');
}

$formAction = JRoute::_('index.php?option=com_easystaging&layout=run&id=' . (int) $this->item->id);
?>

<form action="<?php echo $formAction; ?>" method="post" name="adminForm" id="easystaging-form">
	<div class="span12">
		<div class="form-inline form-inline-header">
<?php
echo $this->form->getControlGroup('name');
echo $this->form->getControlGroup('id');
echo $this->form->getControlGroup('published');
echo $this->form->getValue('description');
?>
		</div>
		<!-- Tab UI -->
		<?php
		echo JHtml::_('bootstrap.startTabSet', 'myTab', array('active' => 'statustab'));
		echo JHtml::_('bootstrap.addTab', 'myTab', 'statustab', JText::_('COM_EASYSTAGING_STATUS_TAB', true));

		// Again this is ugly
		$currentLayout = $this->getLayout();
		$this->setLayout('edit_j3');
		echo $this->loadTemplate('status');

		// But, it's slightly better than duplicating code :(
		$this->setLayout($currentLayout);
		echo JHtml::_('bootstrap.endTab');
		?>
		<?php echo JHtml::_('bootstrap.endTabSet'); ?>
		<input type="hidden" id="id" name="id" value="<?php echo $this->form->getValue('id'); ?>">
		<input type="hidden" id="runOnlyMode" value="<?php echo $this->runOnly; ?>">
		<input type="hidden" name="task" value="plan.run" />
		<?php echo JHtml::_('form.token'); ?>
	</div>
</form>
