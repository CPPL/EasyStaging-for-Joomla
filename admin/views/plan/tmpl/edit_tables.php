<?php
// No direct access to this file
defined('_JEXEC') or die('Restricted Access'); ?>
	<div>
		<p class="tab-description"><?php echo JText::_('COM_EASYSTAGING_TABLESETTINGS_TAB_DESC')?></p>
			<div id="table-filters" title="<?php echo JText::_('COM_EASYSTAGING_JS_TABLE_FILTERS'); ?>">
				<div id="tf-toggle"></div>
				<div id="tf-text">
					<p><?php echo JText::_('JSEARCH_FILTER_LABEL'); ?> </p><input id="tableNamesFilter" type="text">
				</div>
				<div id="tf-presets1">
					<span id="allTablesFilter" class="hasTip" title="<?php echo JText::_('COM_EASYSTAGING_JS_ALL_TABLES_FILTER'); ?>"><button id="allTables" type="button" class="startBtns" ><?php echo JText::_('COM_EASYSTAGING_JS_ALL_TABLES_BTN'); ?></button></span>
					<span id="skippedTablesFilter" class="hasTip" title="<?php echo JText::_('COM_EASYSTAGING_JS_SKIP_TABLES_FILTER'); ?>"><button id="allTables" type="button" class="startBtns" ><?php echo JText::_('COM_EASYSTAGING_JS_SKIP_TABLES_BTN'); ?></button></span>
					<span id="notSkippedTablesFilter" class="hasTip" title="<?php echo JText::_('COM_EASYSTAGING_JS_NOT_SKIP_TABLES_FILTER'); ?>"><button id="allTables" type="button" class="startBtns" ><?php echo JText::_('COM_EASYSTAGING_JS_NOT_SKIP_TABLES_BTN'); ?></button></span>
				</div>
				<div id="tf-presets2">
					<span id="pushTablesFilter" class="hasTip" title="<?php echo JText::_('COM_EASYSTAGING_JS_PUSH_TABLES_FILTER'); ?>"><button id="allTables" type="button" class="startBtns" ><?php echo JText::_('COM_EASYSTAGING_JS_PUSH_TABLES_BTN'); ?></button></span>
					<span id="ptpTablesFilter" class="hasTip" title="<?php echo JText::_('COM_EASYSTAGING_JS_PTP_TABLES_FILTER'); ?>"><button id="allTables" type="button" class="startBtns" ><?php echo JText::_('COM_EASYSTAGING_JS_PTP_TABLES_BTN'); ?></button></span>
					<span id="pullTablesFilter" class="hasTip" title="<?php echo JText::_('COM_EASYSTAGING_JS_PULL_TABLES_FILTER'); ?>"><button id="allTables" type="button" class="startBtns" ><?php echo JText::_('COM_EASYSTAGING_JS_PULL_TABLES_BTN'); ?></button></span>
				</div>
			</div>
		<table class="adminlist">
			<thead><tr><td class="table_name" ><?php echo JText::_('COM_EASYSTAGING_TABLE_NAME_TH') ?></td><td class="action_result" ><?php echo JText::_('COM_EASYSTAGING_TABLE_LAST_ACTION_RESULT_TH') ?></td><td class="action" ><?php echo JText::_('COM_EASYSTAGING_TABLE_ACTION_TH') ?></td></tr></thead>
			<tbody><?php echo $this->loadTemplate('tables_body');?></tbody>
		</table>
	</div>
	<div class="clr"></div>
