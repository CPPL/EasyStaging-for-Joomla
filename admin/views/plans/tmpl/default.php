<?php
// No direct access to this file
defined('_JEXEC') or die('Restricted Access');

$listOrder	= '';
$listDirn	= '';
 ?>
<form action="<?php echo JRoute::_('index.php?option=com_easystaging');?>" method="post" name="adminForm">
	<div id="editcell">
		<table class="adminlist">
			<thead><?php echo $this->loadTemplate('head');?></thead>
			<tfoot><?php echo $this->loadTemplate('foot');?></tfoot>
			<tbody><?php echo $this->loadTemplate('body');?></tbody>
		</table>
	</div>
	<div id="howitworkspanel" class="m">
		<div style="display:none;">
			<div>
			</div>
		</div>
		<h3><?php echo JText::_( 'COM_EASYSTAGING_HOW_IT_WORKS___' ); ?> <a href="javascript:void(0);"><span id="howitworkstoggle" title="<?php echo JText::_( 'COM_EASYSTAGING_PLANS_HOW_IT_WORK_CLICK_HERE_TO_EXPAND' ) ?>" class="hasTip"><?php echo JText::_('COM_EASYSTAGING_EXPANDER'); ?></span></a></h3>
		<div id="howitworksbody">
			<?php echo JText::_( 'COM_EASYSTAGING_HOW_IT_WORKS_DESC' ); ?><hr>
			<div id="howitworksimage1" ><img alt="How to diagram #1" src="<?php echo '../media/com_easystaging/images/EasyStaging-How-To-Part-1-sm.png'; ?>" width=240px height=120px></div>
			<h3><?php echo JText::_( 'COM_EASYSTAGING__ABOUT__RSYNC__DESC' ); ?> </h3>
			<?php echo JText::_( 'COM_EASYSTAGING_HOW_RSYNC_COPIES_DESC' ); ?><hr>
			<div id="howitworksimage2" ><img alt="How to diagram #2" src="<?php echo '../media/com_easystaging/images/EasyStaging-How-To-Part-2-sm.png'; ?>" width=240px height=120px></div>
			<h3><?php echo JText::_( 'COM_EASYSTAGING_ABOUT_DATABASE_SYNCING' ); ?></h3>
			<?php echo JText::_( 'COM_EASYSTAGING_DATABASE_SYNCING_DESC' ) ?>
		</div>
	</div>
	<input type="hidden" name="task" value="" />
	<input type="hidden" name="boxchecked" value="0" />
	<input type="hidden" name="filter_order" value="<?php echo $listOrder; ?>" />
	<input type="hidden" name="filter_order_Dir" value="<?php echo $listDirn; ?>" />
	<?php echo JHtml::_('form.token'); ?>
</form>
