<?php
// No direct access to this file
defined('_JEXEC') or die('Restricted Access');
?>

<div><!-- Plan Controls -->
	<div id="planControls" >
		<?php if ($this->item->published) : ?>
		<input type="hidden" name=<?php echo JSession::getFormToken(); ?> value="1" id="esTokenForJSON" >
		<span id="startFileBtn" class="hasTip" title="<?php echo JText::_('COM_EASYSTAGING_STATUS_START_FILE_DESC'); ?>"><button id="startFile" type="button" class="startBtns" ><?php echo JText::_('COM_EASYSTAGING_STATUS_START_FILE_BTN'); ?></button></span>
		<span id="startDBaseBtn" class="hasTip" title="<?php echo JText::_('COM_EASYSTAGING_STATUS_START_DBASE_DESC'); ?>"><button id="startDBase" type="button" class="startBtns" ><?php echo JText::_('COM_EASYSTAGING_STATUS_START_DBASE_BTN'); ?></button></span>
		<span id="startAllBtn" class="hasTip" title="<?php echo JText::_('COM_EASYSTAGING_STATUS_START_ALL_DESC'); ?>"><button id="startAll" type="button" class="startBtns" ><?php echo JText::_('COM_EASYSTAGING_STATUS_START_ALL_BTN'); ?></button></span>
		<?php endif; ?>
	</div>
	<div id="rsyncErrors"><?php echo JText::_('COM_EASYSTAGING_RSYNC_RUN_ERROR_CODES'); ?></div>
	<div id="lastRunStatus" ><?php
		$last_run = $this->item->last_run;
		$not_run_yet = ($last_run == '0000-00-00 00:00:00' || empty($last_run));

		if ($not_run_yet && $this->item->published)
		{
			echo JText::_('COM_EASYSTAGING_NOT_RUN_LONG');
		}
		elseif ($this->item->published)
		{
			echo JText::sprintf('COM_EASYSTAGING_LAST_RUN', JFactory::getDate($last_run)->format(JText::_('DATE_FORMAT_LC2'), true));
		}
		elseif (!$this->item->published)
		{
			echo JText::_('COM_EASYSTAGING_CANT_RUN_NOT_PUBLSIHED');
		}
	?></div>
	<div class="clearfix"></div>
	<div id="es_testing">
		<div id="es_dbtest">
			<p><img id="dbTest-icon"src="/media/com_easystaging/images/Test-Icon-for-EasyStaging-48.png"><?php echo JText::_('COM_EASYSTAGING_JSON_TEST_REMOTE_DATABASE_DESC'); ?></p>
			<button name="testDBConnection" type="button" onclick="com_EasyStaging.checkDBSettings()"><?php echo JText::_('COM_EASYSTAGING_JSON_TEST_REMOTE_DATABASE'); ?></button>
		</div>
		<div id="es_rstest">
			<p><img id="RSTest-icon"src="/media/com_easystaging/images/Rsync-Test-icon-48.png"><?php echo JText::_('COM_EASYSTAGING_JSON_TEST_RSYNC_DESC'); ?></p>
			<button name="testRsyncWorks" type="button" onclick="com_EasyStaging.checkRsyncWorks()"><?php echo JText::_('COM_EASYSTAGING_JSON_TEST_RSYNC'); ?></button>
		</div>
	</div>
	<div style="clear:left;"></div>
	<div class="planUpdatesDiv adminList" >
		<div id="currentStatus" >
			<?php echo ($not_run_yet && empty($this->item->id)) ? JText::_('COM_EASYSTAGING_JSON_DIV_STATUS_NOT_READY') : JText::_('COM_EASYSTAGING_JSON_DIV_STATUS'); ?>
		</div>
	</div>
</div>
