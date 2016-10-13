<?php defined('_JEXEC') or die('Restricted access');
$listOrder = $this->escape($this->state->get('list.ordering'));
$listDirn  = $this->escape($this->state->get('list.direction'));
?>
<tr>
	<th width="1%" class="center">
		<input type="checkbox" name="toggle" value="" onclick="checkAll(<?php echo count( $this->items ); ?>);" />
	</th>
	<th width="1%"><?php echo JHtml::_('searchtools.sort', 'COM_EASYSTAGING_PUBLISHED', 'published', $listDirn, $listOrder); ?></th>
	<th width="25%" >
        <?php echo JHtml::_('searchtools.sort', 'COM_EASYSTAGING_PLAN', 'name', $listDirn, $listOrder); ?>
	</th>
	<th class="hidden-phone">
		<?php echo JText::_( 'COM_EASYSTAGING_DESCRIPTION' ); ?>
	</th>
	<th width="2%" class="center">
        <?php echo JHtml::_('searchtools.sort', 'JGRID_HEADING_ID', 'id', $listDirn, $listOrder); ?>
	</th>
</tr>            
