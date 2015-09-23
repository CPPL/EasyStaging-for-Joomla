<?php
defined('_JEXEC') or die('Restricted access');

$howtodiag1 = JURI::root().'media/com_easystaging/images/EasyStaging-How-To-Part-1-sm.png';
$howtodiag2 = JURI::root().'media/com_easystaging/images/EasyStaging-How-To-Part-2-sm.png';
?>
<h3><?php echo JText::_( 'COM_EASYSTAGING_HOW_IT_WORKS___' ); ?><a href="javascript:void(0);"><span id="howitworkstoggle" title="<?php echo JText::_( 'COM_EASYSTAGING_PLANS_HOW_IT_WORK_CLICK_HERE_TO_EXPAND' ) ?>" class="hasTip"><?php echo JText::_('COM_EASYSTAGING_EXPANDER'); ?></span></a></h3>
<div id="howitworksbody">
	<?php echo JText::_( 'COM_EASYSTAGING_HOW_IT_WORKS_DESC' ); ?><hr>
	<div id="howitworksimage1" ><img alt="How to diagram #1" src="<?php echo $howtodiag1; ?>" width=240px height=120px></div>
	<h3><?php echo JText::_( 'COM_EASYSTAGING__ABOUT__RSYNC__DESC' ); ?> </h3>
	<?php echo JText::_( 'COM_EASYSTAGING_HOW_RSYNC_COPIES_DESC' ); ?><hr>
	<div id="howitworksimage2" ><img alt="How to diagram #2" src="<?php echo $howtodiag1; ?>" width=240px height=120px></div>
	<h3><?php echo JText::_( 'COM_EASYSTAGING_ABOUT_DATABASE_SYNCING' ); ?></h3>
	<?php echo JText::_( 'COM_EASYSTAGING_DATABASE_SYNCING_DESC' ) ?>
</div>
