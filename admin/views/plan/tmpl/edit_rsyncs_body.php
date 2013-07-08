<?php
// No direct access to this file
defined('_JEXEC') or die('Restricted Access');

$fileCopyActions = $this->item->fileCopyActions;
$i = 0;

foreach ($fileCopyActions as $row)
{
	/**
	 * @todo move all of this into the model
	 */
	// Retrieve values
	$last = $row['last'];
	$lastResult = $row['last_result'];
	$id = $row['id'];

	// Setup ControlName so that within the rsyncSettings each table has its own array
	$controlName = 'fileCopyActions[' . $id . ']';
	$tableRowId = 'fileCopyActions' . $id;


	$directionSelect = $this->_getDirectionMenu($row['direction'], $controlName . '[direction]', $tableRowId);

	// Increment our row index
	$i++;
?>
		<tr class="rsync-settings row<?php echo $i % 2; ?>" id="<?php echo JFilterOutput::stringURLSafe($row['label']); ?>">
			<td><p class="com_easystaging_rsync_label"><input type="text" name="<?php echo $controlName . '[label]" value="' . $row['label']; ?>" size="60"></p><p class="clr">&nbsp;</p>
				<p class="com_easystaging_rsync_last_run"><?php echo $last ?></p>
			<input type="hidden" name="<?php echo $controlName . '[id]" value="' . $id; ?>"></td>
			<td><?php echo $directionSelect; ?></td>
			<td><input class="com_easystaging_rsync_source_path" type="text" name="<?php echo $controlName . '[source_path]" value="' . $row['source_path']; ?>" size="60"></td>
			<td><input class="com_easystaging_rsync_target_path" type="text" name="<?php echo $controlName . '[target_path]" value="' . $row['target_path']; ?>" size="60"></td>
		</tr>
<?php
}
