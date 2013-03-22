<?php
// No direct access to this file
defined('_JEXEC') or die('Restricted Access');

$localTables = $this->item->localTables;
$actionChoices = $this->actionChoices;
$i = 0;

foreach ($localTables as $tablename => $row)
{
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
		<tr class="row<?php echo $i % 2; ?>">
			<td><?php echo $tablename; ?>
			<input type="hidden" name="<?php echo $controlName . '[origAction]" value="' . $actionCurrent; ?>">
			<input type="hidden" name="<?php echo $controlName . '[id]" value="' . $id; ?>"></td>
			<td><span class="com_easystaging_mgr_last_run"><?php echo $lastActionResult; ?></span></td>
			<td><?php echo $actionSelect; ?></td>
		</tr>
<?php
}
