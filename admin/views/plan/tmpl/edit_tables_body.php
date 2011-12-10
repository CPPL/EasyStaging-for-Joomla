<?php
// No direct access to this file
defined('_JEXEC') or die('Restricted Access');

$localTables = $this->item->localTables;
$actionChoices = array( );
$actionChoices[] = array('action' => 0, 'actionLabel' => '-- '.JText::_('COM_EASYSTAGING_TABLE_ACTION0').' --');
$actionChoices[] = array('action' => 1, 'actionLabel' => JText::_('COM_EASYSTAGING_TABLE_ACTION1'));
$actionChoices[] = array('action' => 2, 'actionLabel' => JText::_('COM_EASYSTAGING_TABLE_ACTION2'));
$actionChoices[] = array('action' => 3, 'actionLabel' => JText::_('COM_EASYSTAGING_TABLE_ACTION3'));
$actionChoices[] = array('action' => 4, 'actionLabel' => JText::_('COM_EASYSTAGING_TABLE_ACTION4'));

foreach ($localTables as $i => $row)
{
	// Retrieve values & Sanitize data
	if(array_key_exists('last', $row)){ $last =  $row['last']; } else { $last = '0000-00-00 00:00:00'; }
	if(array_key_exists('lastresult', $row)) { $lastResult =  $row['lastresult']; } else { $lastResult = 0; }
	if(array_key_exists('action', $row)) { $actionCurrent = $row['action']; } else { $actionCurrent = '1'; }
	if(array_key_exists('tablename',$row)) { $tablename = $row['tablename']; } else { JError::raiseError('500',JText::_('COM_EASYSTAGING_ERROR_MISSING_TABLE_NAME'));}
	$id = $row['id'];
	$tableRowId = $tablename.':'.$id;
//	$controlName = $tableRowId. '[' .$tablename. ']';
	$controlName = 'tableAction[' .$tablename. ']';
	
	$lastActionResult = '';
	if($last == '0000-00-00 00:00:00') {
		$lastActionResult = JText::_('COM_EASYSTAGING_TABLE_NO_LAST_ACTION_RESULT');
	} else {
		$last = strtotime( $last );
		$lastResult = ($lastResult ? JText::_('COM_EASYSTAGING_TABLE_LAST_RESULT_SUCCESS') : JText::_('COM_EASYSTAGING_TABLE_LAST_RESULT_FAIL')); 
		$lastActionResult = JText::sprintf('COM_EASYSTAGING_TABLE_LAST_ACTION_RESULT',$last,$lastResult);
	}
	$actionSelect = JHtml::_('select.genericlist', $actionChoices, $controlName, 'class="inputbox"', 'action', 'actionLabel', $actionCurrent, $tableRowId);
	
?>
		<tr class="<?php echo "row" . $i % 2; ?>">
			<td><?php echo $tablename; ?><input type="hidden" name="orig-<?php echo $tableRowId; ?>" value="<?php echo $actionCurrent; ?>"></td>
			<td><span class="com_easystaging_mgr_last_run"><?php echo $lastActionResult; ?></span></td>
			<td><?php echo $actionSelect; ?></td>
		</tr>
<?php
	}
?>
