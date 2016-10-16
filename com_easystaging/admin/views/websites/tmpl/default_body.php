<?php
// No direct access to this file
defined('_JEXEC') or die('Restricted Access');
require_once JPATH_COMPONENT . '/helpers/websites.php';

// Setup for User permissions
$user		= JFactory::getUser();
$userId		= $user->get('id');


$listOrder = $this->escape($this->state->get('list.ordering'));
$listDirn  = $this->escape($this->state->get('list.direction'));


foreach ($this->items as $i => &$row)
{
    list($checked, $state, $plan, $created_by, $last_modified_by) = WebsitesHelper::processWebsiteRow($row, $i, $user, $userId);

    ?>
		<tr class="<?php echo 'row' . $i % 2; ?>">
			<td class="center">
                <div class="btn-group">
                    <?php echo $checked; ?>
                </div>
            </td>
			<td class="center">
                <?php echo $state; ?>
			</td>
            <td class="center"><span class="icon-<?php echo $row->typeIcon; ?> es-large-font-icon" title="<?php echo $row->type; ?>"> </span></td>
			<td><?php echo $plan; ?><br><span class="com_easystaging_mgr_last_modified"><?php echo $last_modified_by; ?></span><span class="com_easystaging_mgr_created_by"><?php echo $created_by; ?></span></td>
			<td class="center"><?php echo $row->id; ?></td>
		</tr>
<?php
	}
?>
