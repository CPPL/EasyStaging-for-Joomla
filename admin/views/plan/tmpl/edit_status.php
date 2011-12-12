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
	<div id="lastRunStatus" ><?php
		if($this->item->last_run == "0000-00-00 00:00:00")
		{
			echo JText::_('COM_EASYSTAGING_NOT_RUN_LONG'); 
		} else {
			echo JText::sprintf('COM_EASYSTAGING_NOT_RUN', $this->item->last_run);
		}
	?></div>
	<div class="planUpdatesDiv adminList" >
		<div id="currentStatus" >
			<?php echo JText::_('COM_EASYSTAGING_JSON_DIV_STATUS'); ?>
		</div>
	</div>
</div>
