<?php
// No direct access to this file
defined('_JEXEC') or die('Restricted Access');
require_once JPATH_COMPONENT . '/helpers/plan.php';

// User permissions
$canDo	= PlanHelper::getActions();
$user		= JFactory::getUser();
$userId		= $user->get('id');

foreach ($this->items as $i => &$row)
{
	// Row State
	$checked = JHTML::_('grid.id', $i, $row->id);
	$published = '';
	$published = JHtml::_('jgrid.published', $row->published, $i, 'plans.', $canDo->get('core.edit.state'), 'cb', $row->publish_up, $row->publish_down);
	$canCheckin	= $user->authorise('core.manage', 'com_checkin') || $row->checked_out == $userId || $row->checked_out == 0;
	$canChange	= $user->authorise('core.edit.state', 'com_easystaging.plan.' . $row->id) && $canCheckin;

	if ($canDo->get('core.edit') && $canCheckin && $canChange)
	{
		$plan = '<a href="' . JRoute::_('index.php?option=com_easystaging&task=plan.edit&id=' . $row->id) . '">' . $row->name . '</a>';
	}
	elseif ($canDo->get('easystaging.run') && !$row->checked_out && $row->published)
	{
		$plan = '<a href="' . JRoute::_('index.php?option=com_easystaging&task=plan.run&id=' . $row->id) . '">' . $row->name . '</a>';
	}
	else
	{
		$plan = $row->name;
	}

	if ($row->checked_out)
	{
		$plan = JHtml::_('jgrid.checkedout', $i, $row->editor, $row->checked_out_time, 'plans.', $canCheckin) . ' ' . $plan;
	}

	$last_run = JHtml::_('date', $row->last_run, JText::_('DATE_FORMAT_LC1'));

	if ($row->last_run == '0000-00-00 00:00:00')
	{
		$last_run = JText::_('COM_EASYSTAGING_NOT_RUN');
	}
	else
	{
		if ($last_run != $row->lastRunDTS)
		{
			$relDate = ' (' . $row->lastRunDTS . ')';
		}
		else
		{
			$relDate = '';
		}

		$last_run = JText::sprintf('COM_EASYSTAGING_LAST_RUN', $last_run) . $relDate;
	}

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
