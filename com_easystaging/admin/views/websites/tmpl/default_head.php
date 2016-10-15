<?php defined('_JEXEC') or die('Restricted access');
$listOrder = $this->escape($this->state->get('list.ordering'));
$listDirn  = $this->escape($this->state->get('list.direction'));
?>
<tr>
	<th width="1%" class="center">
		<div class="btn-group">
            <input type="checkbox" name="toggle" value="" onclick="checkAll(<?php echo count( $this->items ); ?>);" />
        </div>
	</th>
	<th width="1%" class="center"><?php echo JHtml::_('searchtools.sort', 'COM_EASYSTAGING_PUBLISHED', 'state', $listDirn, $listOrder); ?></th>
    <th width="1%" class="center"><?php echo JHtml::_('searchtools.sort', 'COM_EASYSTAGING_SITE_TYPE', 'type', $listDirn, $listOrder); ?></th>
	<th width="25%" >
        <?php echo JHtml::_('searchtools.sort', 'COM_EASYSTAGING_WEBSITE', 'site_name', $listDirn, $listOrder); ?>
	</th>
	<th width="2%" class="center">
        <?php echo JHtml::_('searchtools.sort', 'JGRID_HEADING_ID', 'id', $listDirn, $listOrder); ?>
	</th>
</tr>            
