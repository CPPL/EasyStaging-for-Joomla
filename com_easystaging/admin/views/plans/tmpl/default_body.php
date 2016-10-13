<?php
// No direct access to this file
defined('_JEXEC') or die('Restricted Access');
require_once JPATH_COMPONENT . '/helpers/plans.php';

// Setup for User permissions
$user		= JFactory::getUser();
$userId		= $user->get('id');


foreach ($this->items as $i => &$row)
{
    list($checked, $published, $plan, $last_modified_by, $last_run) = PlansHelper::processRow($row, $i, $user, $userId);

    ?>
		<tr class="<?php echo 'row' . $i % 2; ?>">
			<td><?php echo $checked; ?></td>
			<td class="center">
				<div class="btn-group">
                    <?php echo $published; ?>
                </div>
			</td>
			<td><?php echo $plan; ?><br><span class="com_easystaging_mgr_last_modified"><?php echo $last_modified_by; ?></span></td>
			<td class="hidden-phone"><?php echo $row->description; ?><br /><span class="com_easystaging_mgr_last_run"><?php echo $last_run; ?></span></td>
			<td class="center"><?php echo $row->id; ?></td>
		</tr>
<?php
	}
?>
