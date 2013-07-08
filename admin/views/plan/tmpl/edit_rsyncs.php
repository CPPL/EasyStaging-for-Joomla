<?php
// No direct access to this file
defined('_JEXEC') or die('Restricted Access'); ?>
	<div>
		<p class="tab-description"><?php echo JText::_('COM_EASYSTAGING_RSYNCS_TAB_DESC')?></p>
		<table class="adminlist">
			<thead><tr><td class="rsyncs_label_th" ><?php echo JText::_('COM_EASYSTAGING_RSYNCS_LABEL_TH') ?></td><td class="rsyncs_direction_th" ><?php echo JText::_('COM_EASYSTAGING_RSYNCS_DIRECTION_TH') ?></td><td class="rsyncs_source_th" ><?php echo JText::_('COM_EASYSTAGING_RSYNCS_SOURCE_TH') ?></td><td class="rsyncs_source_th" ><?php echo JText::_('COM_EASYSTAGING_RSYNCS_TARGET_TH') ?></td></tr></thead>
			<tbody><?php echo $this->loadTemplate('rsyncs_body');?></tbody>
		</table>
	</div>
	<div class="clr"></div>
