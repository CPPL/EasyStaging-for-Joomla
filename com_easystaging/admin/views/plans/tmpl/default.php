<?php
// No direct access to this file
defined('_JEXEC') or die('Restricted Access');

$listOrder	= '';
$listDirn	= '';
 ?>
<form action="<?php echo JRoute::_('index.php?option=com_easystaging');?>" method="post" name="adminForm" id="adminForm">
	<div id="editdiv" class="span12">
		<table class="adminlist table table-striped">
			<thead><?php echo $this->loadTemplate('head');?></thead>
			<tfoot><?php echo $this->loadTemplate('foot');?></tfoot>
			<tbody><?php echo $this->loadTemplate('body');?></tbody>
		</table>
		<caption><?php echo $this->pagination->getListFooter(); ?></caption>
	</div>
	<div id="es_version">
		<p><?php echo JText::sprintf('COM_EASYSTAGING_CURRENT_VERSION', $this->current_version); ?></p>
	</div>
	<input type="hidden" name="task" value="" />
	<input type="hidden" name="boxchecked" value="0" />
	<input type="hidden" name="filter_order" value="<?php echo $listOrder; ?>" />
	<input type="hidden" name="filter_order_Dir" value="<?php echo $listDirn; ?>" />
	<?php echo JHtml::_('form.token'); ?>
</form>
