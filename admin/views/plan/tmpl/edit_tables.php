<?php
// No direct access to this file
defined('_JEXEC') or die('Restricted Access'); ?>
	<p class="tab-description"><?php echo JText::_('COM_EASYSTAGING_TABLESETTINGS_TAB_DESC')?></p>
	<table class="adminlist">
		<thead><tr><td class="table_name" ><?php echo JText::_('COM_EASYSTAGING_TABLE_NAME_TH') ?></td><td class="action_result" ><?php echo JText::_('COM_EASYSTAGING_TABLE_LAST_ACTION_RESULT_TH') ?></td><td class="action" ><?php echo JText::_('COM_EASYSTAGING_TABLE_ACTION_TH') ?></td></tr></thead>
		<tbody><?php echo $this->loadTemplate('tables_body');?></tbody>
	</table>
	<div class="clr"></div>
