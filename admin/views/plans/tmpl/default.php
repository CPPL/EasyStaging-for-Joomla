<?php
// No direct access to this file
defined('_JEXEC') or die('Restricted Access');

$listOrder	= '';
$listDirn	= '';
 ?>
<form action="<?php echo JRoute::_('index.php?option=com_easystaging');?>" method="post" name="adminForm" id="adminForm">
<?php if ($this->jvtag !== 'j3') : ?>
	<div id="editcell">
<?php else : ?>
	<div id="editdiv" class="span12">
<?php endif; ?>
		<table class="adminlist table table-striped">
			<thead><?php echo $this->loadTemplate($this->jvtag . '_head');?></thead>
			<tfoot><?php echo $this->loadTemplate($this->jvtag . '_foot');?></tfoot>
			<tbody><?php echo $this->loadTemplate($this->jvtag . '_body');?></tbody>
		</table>
	</div>
<?php if ($this->jvtag === 'j2') : ?>
	<div id="howitworkspanel" class="m">
		<div id="es_version">
			<p><?php echo JText::sprintf('COM_EASYSTAGING_CURRENT_VERSION', $this->current_version); ?></p>
		</div>
		<div style="display:none;">
			<div>
			</div>
		</div>
		<?php echo $this->loadTemplate($this->jvtag . '_hiw'); ?>
	</div>
<?php else: ?>
	<div id="es_version">
		<p><?php echo JText::sprintf('COM_EASYSTAGING_CURRENT_VERSION', $this->current_version); ?></p>
	</div>
<?php endif; ?>
	<input type="hidden" name="task" value="" />
	<input type="hidden" name="boxchecked" value="0" />
	<input type="hidden" name="filter_order" value="<?php echo $listOrder; ?>" />
	<input type="hidden" name="filter_order_Dir" value="<?php echo $listDirn; ?>" />
	<?php echo JHtml::_('form.token'); ?>
</form>
