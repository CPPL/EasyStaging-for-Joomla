<?php
// No direct access to this file
defined('_JEXEC') or die('Restricted Access');
?>
<div>
	<div id="planControls" >
		<input type="hidden" name=<?php echo JUtility::getToken(); ?> value="1" id="esTokenForJSON" >
		<span id="startFileBtn" class="hasTip" title="<?php echo JText::_('COM_EASYSTAGING_STATUS_START_FILE_DESC'); ?>"><button id="startFile" type="button" class="startBtns" ><?php echo JText::_('COM_EASYSTAGING_STATUS_START_FILE_BTN'); ?></button></span>
		<span id="startDBaseBtn" class="hasTip" title="<?php echo JText::_('COM_EASYSTAGING_STATUS_START_DBASE_DESC'); ?>"><button id="startDBase" type="button" class="startBtns" ><?php echo JText::_('COM_EASYSTAGING_STATUS_START_DBASE_BTN'); ?></button></span>
		<span id="startAllBtn" class="hasTip" title="<?php echo JText::_('COM_EASYSTAGING_STATUS_START_ALL_DESC'); ?>"><button id="startAll" type="button" class="startBtns" ><?php echo JText::_('COM_EASYSTAGING_STATUS_START_ALL_BTN'); ?></button></span>
	</div>
	<div id="rsyncErrors"><?php echo JText::_('COM_EASYSTAGING_RSYNC_RUN_ERROR_CODES'); ?></div>
	<div id="lastRunStatus" ><?php
		$last_run = $this->item->last_run;
		$not_run_yet = ($last_run == '0000-00-00 00:00:00' || empty($last_run));
		if ($not_run_yet)
		{
			echo JText::_('COM_EASYSTAGING_NOT_RUN_LONG');
		}
		else
		{
			echo JText::sprintf('COM_EASYSTAGING_LAST_RUN',JFactory::getDate($last_run)->format(JText::_('DATE_FORMAT_LC2'),true));
		}
	?></div>
	<div style="clear:left;"></div>
	<div class="planUpdatesDiv adminList" >
		<div id="currentStatus" >
			<?php echo ($not_run_yet && empty($this->item->id)) ? JText::_('COM_EASYSTAGING_JSON_DIV_STATUS_NOT_READY') : JText::_('COM_EASYSTAGING_JSON_DIV_STATUS'); ?>
		</div>
	</div>
</div>
