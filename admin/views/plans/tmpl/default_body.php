<?php
// No direct access to this file
defined('_JEXEC') or die('Restricted Access');
require_once JPATH_COMPONENT . '/helpers/plan.php';

// User permissions
$canDo	= PlanHelper::getActions();

foreach ($this->items as $i => &$row)
{
	// Row State
	$checked = JHTML::_('grid.id', $i, $row->id);
	$published = '';
	$published = JHtml::_('jgrid.published', $row->published, $i, 'plans.', $canDo->get('core.edit.state'), 'cb', $row->publish_up, $row->publish_down);

	if ($canDo->get('core.edit'))
	{
		$plan = '<a href="' . JRoute::_('index.php?option=com_easystaging&task=plan.edit&id=' . $row->id) . '">' . $row->name . '</a>';
	}
	elseif ($canDo->get('easystaging.run'))
	{
		$plan = '<a href="' . JRoute::_('index.php?option=com_easystaging&task=plan.run&id=' . $row->id) . '">' . $row->name . '</a>';
	}
	else
	{
		$plan = $row->name;
	}

	$last_run = ($row->last_run == '0000-00-00 00:00:00')
		? JText::_('COM_EASYSTAGING_NOT_RUN')
		: JText::sprintf('COM_EASYSTAGING_LAST_RUN', JHtml::_('date', $row->last_run, JText::_('DATE_FORMAT_LC1')));

?>
		<tr class="<?php echo 'row' . $i % 2; ?>">
			<td><?php echo $checked; ?></td>
			<td><?php echo $plan; ?></td>
			<td><?php echo $row->description; ?><br /><span class="com_easystaging_mgr_last_run"><?php echo $last_run; ?></span></td>
			<td><?php echo $published; ?></td>
			<td><?php echo $row->id; ?></td>
		</tr>
<?php
	}
?>
