<?php
// No direct access to this file
defined('_JEXEC') or die('Restricted Access');

$tabDesc = JText::_('COM_EASYSTAGING_TABLESETTINGS_TAB_DESC');
$filterTogTT = JHtml::tooltipText(JText::_('COM_EASYSTAGING_JS_TABLE_FILTER_BTNS') . '::' . JText::_('COM_EASYSTAGING_JS_TABLE_FILTERS_TT'));
$textFilter = JText::_('COM_EASYSTAGING_JS_TABLE_FILTER');
$textFilterPH = JText::_('COM_EASYSTAGING_TABLE_FILTER_BY_NAME_PH');
$textFilterTT = JHtml::tooltipText($textFilter . '::' . JText::_('COM_EASYSTAGING_TABLE_FILTER_BY_NAME'));

$allTablesBtn = JText::_('COM_EASYSTAGING_JS_ALL_TABLES_BTN');
$allTablesBtnTT = JHtml::tooltipText($allTablesBtn . '::' . JText::_('COM_EASYSTAGING_JS_ALL_TABLES_FILTER'));
$skipTablesBtn = JText::_('COM_EASYSTAGING_JS_SKIP_TABLES_BTN');
$skipTablesBtnTT = JHtml::tooltipText($skipTablesBtn . '::' . JText::_('COM_EASYSTAGING_JS_SKIP_TABLES_FILTER'));
$notSkipTablesBtn = JText::_('COM_EASYSTAGING_JS_NOT_SKIP_TABLES_BTN');
$notSkipTablesBtnTT = JHtml::tooltipText($skipTablesBtn . '::' . JText::_('COM_EASYSTAGING_JS_NOT_SKIP_TABLES_FILTER'));

$pushTablesBtn = JText::_('COM_EASYSTAGING_JS_PUSH_TABLES_BTN');
$pushTablesBtnTT = JHtml::tooltipText($pushTablesBtn . '::' . JText::_('COM_EASYSTAGING_JS_PUSH_TABLES_FILTER'));
$pthpTablesBtn = JText::_('COM_EASYSTAGING_JS_PTP_TABLES_BTN');
$pthpTablesBtnTT = JHtml::tooltipText($pthpTablesBtn . '::' . JText::_('COM_EASYSTAGING_JS_PTP_TABLES_FILTER'));
$pullTablesBtn = JText::_('COM_EASYSTAGING_JS_PULL_TABLES_BTN');
$pullTablesBtnTT = JHtml::tooltipText($pullTablesBtn . '::' . JText::_('COM_EASYSTAGING_JS_PULL_TABLES_FILTER'));

$tableNameTH = JText::_('COM_EASYSTAGING_TABLE_NAME_TH');
$lastActionTH = JText::_('COM_EASYSTAGING_TABLE_LAST_ACTION_RESULT_TH');
$tableActionTH = JText::_('COM_EASYSTAGING_TABLE_ACTION_TH');
?>
	<div>
		<p class="tab-description"><?php echo $tabDesc; ?></p>
			<div id="table-filter-div">
				<span id="tf-toggle"  title="<?php echo $filterTogTT; ?>" class="hasTooltip icon-tf-toggle"></span>
				<div id="tf-text">
					<p title="<?php echo $textFilterTT; ?>" class="hasTooltip">
						<?php echo $textFilter; ?> <input id="tableNamesFilter" type="text" placeholder="<?php echo $textFilterPH ?>">
					</p>
				</div>
				<div id="tf-presets1">
					<button id="showAllTablesBtn" type="button" class="btn hasTooltip" title="<?php echo $allTablesBtnTT; ?>">
						<?php echo $allTablesBtn; ?>
					</button>
					<button id="skippedTablesBtn" type="button" class="btn btnsmall hasTooltip" title="<?php echo $skipTablesBtnTT; ?>">
						<?php echo $skipTablesBtn; ?>
					</button>
					<button id="notSkipTablesBtn" type="button" class="btn btnsmall hasTooltip" title="<?php echo $notSkipTablesBtnTT; ?>">
						<?php echo $notSkipTablesBtn; ?>
					</button>
				</div>
				<div id="tf-presets2">
					<button id="pushTablesBtn" type="button" class="btn hasTooltip" title="<?php echo $pushTablesBtnTT; ?>">
						<?php echo $pushTablesBtn; ?>
					</button>
					<button id="ptpTablesBtn" type="button" class="btn hasTooltip" title="<?php echo $pthpTablesBtnTT; ?>">
						<?php echo $pthpTablesBtn; ?>
					</button>
					<button id="pullTablesBtn" type="button" class="btn hasTooltip" title="<?php echo $pullTablesBtnTT; ?>">
						<?php echo $pullTablesBtn; ?>
					</button>
				</div>
			</div>
		<table class="table-striped table" id="et_tables_table">
			<thead>
				<tr>
					<td><?php echo $tableNameTH; ?></td>
					<td class="hidden-phone"><?php echo $lastActionTH; ?></td>
					<td><?php echo $tableActionTH; ?></td>
				</tr>
			</thead>
			<tbody>
				<?php echo $this->loadTemplate($this->jvtag . '_tables_body');?>
			</tbody>
		</table>
	</div>
