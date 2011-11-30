<?php
// No direct access to this file
defined('_JEXEC') or die('Restricted Access');

$user		= JFactory::getUser();
$userId		= $user->get('id');

foreach ($this->items as $i => &$row)
{
	// User permissions
	$canCreate	= $user->authorise('core.create',		'com_easystaging.plan.'.$row->id);
	$canEdit	= $user->authorise('core.edit',			'com_easystaging.plan.'.$row->id);
	$canCheckin	= $user->authorise('core.manage',		'com_easystaging.plan') || $row->checked_out == $userId || $row->checked_out == 0;
	$canEditOwn	= $user->authorise('core.edit.own',		'com_easystaging.plan.'.$row->id) && $row->created_by == $userId;
	$canChange	= $user->authorise('core.edit.state',	'com_easystaging.plan.'.$row->id) && $canCheckin;

	// Row State
	$checked = JHTML::_( 'grid.id', $i, $row->id );
	$published = '';
	$published = JHtml::_('jgrid.published', $row->published, $i, 'plans.', $canChange, 'cb', $row->publish_up, $row->publish_down);
	$plan = $canEdit ? ('<a href="'.JRoute::_( 'index.php?option=com_easystaging&task=plan.edit&id='. $row->id ).'">'.$row->name.'</a>'):$row->name;
	$last_run = ($row->last_run == '0000-00-00 00:00:00') ? JText::_('COM_EASYSTAGING_NOT_RUN') : JText::sprintf('COM_EASYSTAGING_LAST_RUN', JHtml::_('date',$row->last_run, JText::_('DATE_FORMAT_LC1')));

?>
		<tr class="<?php echo "row" . $i % 2; ?>">
			<td>
				<?php echo $checked; ?>
			</td>
			<td>
				<?php echo $plan; ?>
			</td>
			<td>
				<?php echo $row->description; ?><br /><span class="com_easystaging_mgr_last_run"><?php echo $last_run; ?></span>
			</td>
			<td>
				<?php echo $published; ?>
			</td>
		</tr>
<?php
	}
?>
