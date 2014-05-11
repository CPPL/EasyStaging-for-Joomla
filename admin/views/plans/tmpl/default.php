<?php
// No direct access to this file
defined('_JEXEC') or die('Restricted Access');

$listOrder	= '';
$listDirn	= '';
 ?>
<form action="<?php echo JRoute::_('index.php?option=com_easystaging');?>" method="post" name="adminForm">
	<div id="editcell">
		<table class="adminlist">
			<thead><?php echo $this->loadTemplate($this->jvtag . '_head');?></thead>
			<tfoot><?php echo $this->loadTemplate($this->jvtag . '_foot');?></tfoot>
			<tbody><?php echo $this->loadTemplate($this->jvtag . '_body');?></tbody>
		</table>
	</div>
	<div id="howitworkspanel" class="m">
		<div id="es_version">
			<p><?php echo JText::sprintf('COM_EASYSTAGING_CURRENT_VERSION',$this->current_version); ?></p>
		</div>
		<div style="display:none;">
			<div>
			</div>
		</div>
		<?php echo $this->loadTemplate($this->jvtag . '_hiw'); ?>
	</div>
	<input type="hidden" name="task" value="" />
	<input type="hidden" name="boxchecked" value="0" />
	<input type="hidden" name="filter_order" value="<?php echo $listOrder; ?>" />
	<input type="hidden" name="filter_order_Dir" value="<?php echo $listDirn; ?>" />
	<?php echo JHtml::_('form.token'); ?>
</form>
