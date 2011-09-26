<?php defined('_JEXEC') or die('Restricted access');

JHtml::addIncludePath(JPATH_COMPONENT.'/helpers/html');
JHtml::_('behavior.tooltip');
JHtml::_('behavior.multiselect');

$user		= JFactory::getUser();
$userId		= $user->get('id');

 ?>
<form action="index.php" method="post" name="adminForm">
<div id="editcell">
	<table class="adminlist">
	<thead>
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
		</tr>            
	</thead>
	<?php
	$k = 0;$i = 0;
	foreach ($this->items as &$row)
	{	// User permissions
		$canCreate	= $user->authorise('core.create',		'com_easystaging.plan.'.$row->id);
		$canEdit	= $user->authorise('core.edit',			'com_easystaging.plan.'.$row->id);
		$canCheckin	= $user->authorise('core.manage',		'com_checkin') || $row->checked_out == $userId || $row->checked_out == 0;
		$canEditOwn	= $user->authorise('core.edit.own',		'com_easystaging.plan.'.$row->id) && $row->created_by == $userId;
		$canChange	= $user->authorise('core.edit.state',	'com_easystaging.plan.'.$row->id) && $canCheckin;

		// Row State
		$checked = JHTML::_( 'grid.id', $i, $row->id );
		$published = '';
		$published = JHtml::_('jgrid.published', $row->published, $i, '.plans', $canChange, 'cb', $row->publish_up, $row->publish_down);
		$link = '<a href="'.JRoute::_( 'index.php?option=com_easystaging&controller=easystaging_plan&task=edit&cid[]='. $row->id ).'">'.$row->name.'</a>';	

		if ($row->checked_out) {
			$checked = JHtml::_('jgrid.checkedout', $i, $row->editor, $row->checked_out_time, 'articles.', $canCheckin); 
		}
		
		?>
		<tr class="<?php echo "row" . $k; ?>">
			<td>
				<?php echo $checked; ?>
			</td>
			<td>
				<?php echo $link; ?>
			</td>
			<td>
				<?php echo $row->description; ?>
			</td>
			<td>
				<?php echo $published; ?>
			</td>
		</tr>
		<?php
		$i++;
		$k = 1 - $k;
	}
	?>
	</table>
</div>
<div id="howtopanel" class="m">
<h2><?php echo JText::_( 'COM_EASYSTAGING_HOW_IT_WORKS___' ); ?></h2>
<?php echo JText::_( 'COM_EASYSTAGING_HOW_IT_WORKS_DESC' ); ?>
<h3><?php echo JText::_( 'COM_EASYSTAGING__ABOUT__RSYNC__DESC' ); ?> </h3>
<?php echo JText::_( 'COM_EASYSTAGING_HOW_RSYNC_COPIES_DESC' ); ?>
<h3>About Database Syncing</h3>
<p>EasyStaging will copy tables from your local "staging" websites to the remote "live" websites database.<em>(By default EasyStaging will not copy the #__session table to prevent users from being disconnected.)</em></p>
</div>
 
<input type="hidden" name="option" value="com_easystaging" />
<input type="hidden" name="task" value="" />
<input type="hidden" name="boxchecked" value="0" />
<input type="hidden" name="controller" value="easystaging" />
 
</form>
