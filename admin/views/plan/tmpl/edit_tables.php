<?php
// No direct access to this file
defined('_JEXEC') or die('Restricted Access'); ?>
	<div>
		<p class="tab-description"><?php echo JText::_('COM_EASYSTAGING_TABLESETTINGS_TAB_DESC')?>
			<span id="table-filters" title="<?php echo JText::_('COM_EASYSTAGING_JS_TABLE_FILTERS'); ?>"><?php echo JText::_('JGLOBAL_FILTER_TYPE_LABEL'); ?>:&nbsp;
				<span id="allTablesFilter" class="hasTip" title="<?php echo JText::_('COM_EASYSTAGING_JS_ALL_TABLES_FILTER'); ?>"><button id="allTables" type="button" class="startBtns" ><?php echo JText::_('COM_EASYSTAGING_JS_ALL_TABLES_BTN'); ?></button></span>
				<span id="skippedTablesFilter" class="hasTip" title="<?php echo JText::_('COM_EASYSTAGING_JS_SKIP_TABLES_FILTER'); ?>"><button id="allTables" type="button" class="startBtns" ><?php echo JText::_('COM_EASYSTAGING_JS_SKIP_TABLES_BTN'); ?></button></span>
				<span id="pushTablesFilter" class="hasTip" title="<?php echo JText::_('COM_EASYSTAGING_JS_PUSH_TABLES_FILTER'); ?>"><button id="allTables" type="button" class="startBtns" ><?php echo JText::_('COM_EASYSTAGING_JS_PUSH_TABLES_BTN'); ?></button></span>
				<span id="ptpTablesFilter" class="hasTip" title="<?php echo JText::_('COM_EASYSTAGING_JS_PTP_TABLES_FILTER'); ?>"><button id="allTables" type="button" class="startBtns" ><?php echo JText::_('COM_EASYSTAGING_JS_PTP_TABLES_BTN'); ?></button></span>
				<span id="pullTablesFilter" class="hasTip" title="<?php echo JText::_('COM_EASYSTAGING_JS_PULL_TABLES_FILTER'); ?>"><button id="allTables" type="button" class="startBtns" ><?php echo JText::_('COM_EASYSTAGING_JS_PULL_TABLES_BTN'); ?></button></span>
			</span>
		</p>
		<table class="adminlist">
			<thead><tr><td class="table_name" ><?php echo JText::_('COM_EASYSTAGING_TABLE_NAME_TH') ?></td><td class="action_result" ><?php echo JText::_('COM_EASYSTAGING_TABLE_LAST_ACTION_RESULT_TH') ?></td><td class="action" ><?php echo JText::_('COM_EASYSTAGING_TABLE_ACTION_TH') ?></td></tr></thead>
			<tbody><?php echo $this->loadTemplate('tables_body');?></tbody>
		</table>
	</div>
	<div class="clr"></div>
