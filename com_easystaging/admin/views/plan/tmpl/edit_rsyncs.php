<?php
// No direct access to this file
defined('_JEXEC') or die('Restricted Access'); ?>
	<div>
		<p class="tab-description"><?php echo JText::_('COM_EASYSTAGING_RSYNCS_TAB_DESC')?></p>
		<table class="table table-striped">
			<thead>
				<tr>
					<td><?php echo JText::_('COM_EASYSTAGING_RSYNCS_LABEL_TH') ?></td>
					<td><?php echo JText::_('COM_EASYSTAGING_RSYNCS_DIRECTION_TH') ?></td>
					<td><?php echo JText::_('COM_EASYSTAGING_RSYNCS_SOURCE_TH') ?></td>
					<td><?php echo JText::_('COM_EASYSTAGING_RSYNCS_TARGET_TH') ?></td>
				</tr>
			</thead>
			<tbody>
				<?php echo $this->loadTemplate('rsyncs_body');?>
			</tbody>
		</table>
	</div>
