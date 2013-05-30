<?php
// No direct access to this file
defined('_JEXEC') or die('Restricted Access');
?>

<div><!-- Plan Controls -->
	<?php if ($this->item->published) : ?>
	<div id="planControls" >
		<input type="hidden" name=<?php echo JUtility::getToken(); ?> value="1" id="esTokenForJSON" >
		<span id="startFileBtn" class="hasTip" title="<?php echo JText::_('COM_EASYSTAGING_STATUS_START_FILE_DESC'); ?>"><button id="startFile" type="button" class="startBtns" ><?php echo JText::_('COM_EASYSTAGING_STATUS_START_FILE_BTN'); ?></button></span>
		<span id="startDBaseBtn" class="hasTip" title="<?php echo JText::_('COM_EASYSTAGING_STATUS_START_DBASE_DESC'); ?>"><button id="startDBase" type="button" class="startBtns" ><?php echo JText::_('COM_EASYSTAGING_STATUS_START_DBASE_BTN'); ?></button></span>
		<span id="startAllBtn" class="hasTip" title="<?php echo JText::_('COM_EASYSTAGING_STATUS_START_ALL_DESC'); ?>"><button id="startAll" type="button" class="startBtns" ><?php echo JText::_('COM_EASYSTAGING_STATUS_START_ALL_BTN'); ?></button></span>
	</div>
	<?php endif; ?>
	<div id="rsyncErrors"><?php echo JText::_('COM_EASYSTAGING_RSYNC_RUN_ERROR_CODES'); ?></div>
	<div id="lastRunStatus" ><?php
		$last_run = $this->item->last_run;
		$not_run_yet = ($last_run == '0000-00-00 00:00:00' || empty($last_run));

		if ($not_run_yet && $this->item->published)
		{
			echo JText::_('COM_EASYSTAGING_NOT_RUN_LONG');
		}
		elseif($this->item->published)
		{
			echo JText::sprintf('COM_EASYSTAGING_LAST_RUN', JFactory::getDate($last_run)->format(JText::_('DATE_FORMAT_LC2'), true));
		}
		elseif(!$this->item->published)
		{
			echo JText::_('COM_EASYSTAGING_CANT_RUN_NOT_PUBLSIHED');
		}
	?></div>
	<div style="clear:left;"></div>
	<div class="planUpdatesDiv adminList" >
		<div id="currentStatus" >
			<?php echo ($not_run_yet && empty($this->item->id)) ? JText::_('COM_EASYSTAGING_JSON_DIV_STATUS_NOT_READY') : JText::_('COM_EASYSTAGING_JSON_DIV_STATUS'); ?>
		</div>
	</div>
</div>
