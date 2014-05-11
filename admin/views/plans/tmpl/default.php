<?php
// No direct access to this file
defined('_JEXEC') or die('Restricted Access');

$listOrder	= '';
$listDirn	= '';
$howtodiag1 = JURI::root().'media/com_easystaging/images/EasyStaging-How-To-Part-1-sm.png';
$howtodiag2 = JURI::root().'media/com_easystaging/images/EasyStaging-How-To-Part-2-sm.png';
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
		<h3><?php echo JText::_( 'COM_EASYSTAGING_HOW_IT_WORKS___' ); ?> <a href="javascript:void(0);"><span id="howitworkstoggle" title="<?php echo JText::_( 'COM_EASYSTAGING_PLANS_HOW_IT_WORK_CLICK_HERE_TO_EXPAND' ) ?>" class="hasTip"><?php echo JText::_('COM_EASYSTAGING_EXPANDER'); ?></span></a></h3>
		<div id="howitworksbody">
			<?php echo JText::_( 'COM_EASYSTAGING_HOW_IT_WORKS_DESC' ); ?><hr>
			<div id="howitworksimage1" ><img alt="How to diagram #1" src="<?php echo $howtodiag1; ?>" width=240px height=120px></div>
			<h3><?php echo JText::_( 'COM_EASYSTAGING__ABOUT__RSYNC__DESC' ); ?> </h3>
			<?php echo JText::_( 'COM_EASYSTAGING_HOW_RSYNC_COPIES_DESC' ); ?><hr>
			<div id="howitworksimage2" ><img alt="How to diagram #2" src="<?php echo $howtodiag1; ?>" width=240px height=120px></div>
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
