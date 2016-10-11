<?php
// No direct access
defined('_JEXEC') or die('Restricted access');

// Load the tooltip behavior.
JHtml::_('behavior.formvalidation');
JHtml::_('formbehavior.chosen', 'select');

if (!$this->canDo->get('core.edit'))
{
	$app =& JFactory::getApplication();
	$app->redirect('index.php?option=com_easystaging');

	return JError::raiseWarning(404, JText::_('JERROR_ALERTNOAUTHOR'));
}

$formAction = JRoute::_('index.php?option=com_easystaging&layout=edit&id=' . (int) $this->item->id);
?>
<form action="<?php echo $formAction ?>" method="post" name="adminForm" id="easystaging-form">
<div class="span12">
	<div class="form-inline form-inline-header">
<?php
echo $this->form->getControlGroup('name');
echo $this->form->getControlGroup('id');
?>
	</div>
<?php echo JHtml::_('bootstrap.startTabSet', 'myTab', array('active' => 'statustab')); ?>
<?php
	if ($this->canDo->get('easystaging.run'))
	{
		echo JHtml::_('bootstrap.addTab', 'myTab', 'statustab', JText::_('COM_EASYSTAGING_STATUS_TAB', true));
		echo $this->loadTemplate('status');
		echo JHtml::_('bootstrap.endTab');
	}

	echo JHtml::_('bootstrap.addTab', 'myTab', 'details', JText::_('COM_EASYSTAGING_PLAN_DETAILS', true));
	echo $this->loadTemplate('plan');
	echo JHtml::_('bootstrap.endTab');

	if ($this->canDo->get('core.edit') || ($this->canDo->get('core.create') && ($this->item->id == 0)))
	{
		echo JHtml::_('bootstrap.addTab', 'myTab', 'local_site', JText::_('COM_EASYSTAGING_LOCALSITE_TAB', true));
		echo $this->loadTemplate('local');
		echo JHtml::_('bootstrap.endTab');

		echo JHtml::_('bootstrap.addTab', 'myTab', 'remote_site', JText::_('COM_EASYSTAGING_REMOTESITE_TAB', true));
		echo $this->loadTemplate('remote');
		echo JHtml::_('bootstrap.endTab');

		echo JHtml::_('bootstrap.addTab', 'myTab', 'table_settings', JText::_('COM_EASYSTAGING_TABLESETTINGS_TAB', true));
		echo $this->loadTemplate('tables');
		echo JHtml::_('bootstrap.endTab');

		echo JHtml::_('bootstrap.addTab', 'myTab', 'fileCopyActions', JText::_('COM_EASYSTAGING_RSYNCS_TAB', true));
		echo $this->loadTemplate('rsyncs');
		echo JHtml::_('bootstrap.endTab');
	}

	if ($this->canDo->get('core.admin'))
	{
		echo JHtml::_('bootstrap.addTab', 'myTab', 'permissions', JText::_('COM_EASYSTAGING_FIELDSET_RULES', true));
		echo $this->form->getInput('rules');
		echo JHtml::_('bootstrap.endTab');
	}
?>
	<?php echo JHtml::_('bootstrap.endTabSet'); ?>
	<input type="hidden" id="id" name="id" value="<?php echo $this->form->getValue('id'); ?>">
	<input type="hidden" id="runOnlyMode" value="<?php echo $this->runOnly; ?>">
	<input type="hidden" name="task" value="plan.edit" />
	<?php echo JHtml::_('form.token'); ?>
</div>

</form>

</div>
