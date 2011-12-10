<?php defined('_JEXEC') or die('Restricted access'); ?>
<tr>
	<th width="20">
		<input type="checkbox" name="toggle" value="" onclick="checkAll(<?php echo count( $this->items ); ?>);" />
	</th>
	<th>
		<?php echo JText::_( 'COM_EASYSTAGING_PLAN' ); ?>
	</th>
	<th>
		<?php echo JText::_( 'COM_EASYSTAGING_DESCRIPTION' ); ?>
	</th>
	<th width="20">
		<?php echo JText::_( 'COM_EASYSTAGING_PUBLISHED' ); ?>
	</th>
	<th>
		<?php echo JText::_( 'JGRID_HEADING_ID' ); ?>
	</th>
</tr>            
