<?php
// No direct access to this file
defined('_JEXEC') or die('Restricted Access');
?>
<div>
	<div id="planControls" ><button type="button" class="startFileSync" onclick="">Start File Sync</button><br /></div>
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
