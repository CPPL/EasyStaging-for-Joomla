<?php
// No direct access to this file
defined('_JEXEC') or die('Restricted Access');

$localTables = $this->item->localTables;
$i = 0;

foreach ($localTables as $tablename => $row)
{
	/**
	 * @todo move all of this into the model
	 */
	// Retrieve values & Sanitize data
	if (array_key_exists('last', $row))
	{
		$last = $row['last'];
	}
	else
	{
		$last = '0000-00-00 00:00:00';
	}

	if (array_key_exists('lastresult', $row))
	{
		$lastResult = $row['lastresult'];
	}
	else
	{
		$lastResult = 0;
	}

	if (array_key_exists('action', $row))
	{
		$actionCurrent = $row['action'];
	}
	else
	{
		$actionCurrent = '1';
	}

	$id = $row['id'];

	// Setup ControlName so that within the tablesettings each table has its own array
	$controlName = 'tableSettings[' . $tablename . ']';
	$tableRowId = 'tableSettings_' . $tablename . '_' . $id;

	$lastActionResult = '';

	if ($last == '0000-00-00 00:00:00')
	{
		$lastActionResult = JText::_('COM_EASYSTAGING_TABLE_NO_LAST_ACTION_RESULT');
	}
	else
	{
		$last = strtotime($last);
		$lastResult = ($lastResult ? JText::_('COM_EASYSTAGING_TABLE_LAST_RESULT_SUCCESS') : JText::_('COM_EASYSTAGING_TABLE_LAST_RESULT_FAIL'));
		$lastActionResult = JText::sprintf('COM_EASYSTAGING_TABLE_LAST_ACTION_RESULT', $last, $lastResult);
	}

	$actionSelect = $this->_getActionMenu($actionCurrent, $controlName . '[action]', $tableRowId);

	// Increment our row index
	$i++;
?>
		<tr class="table-settings row<?php echo $i % 2; ?>" id="<?php echo $tablename; ?>">
			<td><span><?php echo $tablename; ?></span>
			<input type="hidden" name="<?php echo $controlName . '[origAction]" value="' . $actionCurrent; ?>">
			<input type="hidden" name="<?php echo $controlName . '[id]" value="' . $id; ?>"></td>
			<td class="hidden-phone"><span class="com_easystaging_mgr_last_run"><?php echo $lastActionResult; ?></span></td>
			<td class="tableactioncell"><?php echo $actionSelect; ?></td>
		</tr>
<?php
}
?>
<tr class="table-settings hidden " id="noMatch">
	<td colspan="3"><span class="com_easystaging_mgr_last_run"><?php echo JText::_("COM_EASYSTAGING_TABLES_NO_MATCHING_TABLES"); ?></span></td>
</tr>
