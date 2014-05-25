<?php defined('_JEXEC') or die('Restricted access'); ?>
<tr>
	<th width="1%" class="center">
		<input type="checkbox" name="toggle" value="" onclick="checkAll(<?php echo count( $this->items ); ?>);" />
	</th>
	<th width="25%" >
		<?php echo JText::_( 'COM_EASYSTAGING_PLAN' ); ?>
	</th>
	<th class="hidden-phone">
		<?php echo JText::_( 'COM_EASYSTAGING_DESCRIPTION' ); ?>
	</th>
	<th width="1%" class="center">
		<?php echo JText::_( 'COM_EASYSTAGING_PUBLISHED' ); ?>
	</th>
	<th width="1%" class="center">
		<?php echo JText::_( 'JGRID_HEADING_ID' ); ?>
	</th>
</tr>            
