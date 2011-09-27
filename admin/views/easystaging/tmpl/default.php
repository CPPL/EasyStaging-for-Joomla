<?php defined('_JEXEC') or die('Restricted access');
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
<div id="howitworkspanel" class="m">
	<div style="display:none;">
		<div>
		</div>
	</div>
	<h3><?php echo JText::_( 'COM_EASYSTAGING_HOW_IT_WORKS___' ); ?> <a href="javascript:void(0);"><span id="howitworkstoggle" title="How It Works::Click here to expand." class="hasTip"><?php echo JText::_('COM_EASYSTAGING_EXPANDER'); ?></span></a></h3>
	<div id="howitworksbody">
		<?php echo JText::_( 'COM_EASYSTAGING_HOW_IT_WORKS_DESC' ); ?><hr>
		<div id="howitworksimage1" ><img alt="How to diagram #1" src="<?php echo JURI::base().'components/com_easystaging/assets/media/EasyStaging-How-To-Part-1-sm.png'; ?>" width=240px height=120px></div>
		<h3><?php echo JText::_( 'COM_EASYSTAGING__ABOUT__RSYNC__DESC' ); ?> </h3>
		<?php echo JText::_( 'COM_EASYSTAGING_HOW_RSYNC_COPIES_DESC' ); ?><hr>
		<div id="howitworksimage2" ><img alt="How to diagram #2" src="<?php echo JURI::base().'components/com_easystaging/assets/media/EasyStaging-How-To-Part-2-sm.png'; ?>" width=240px height=120px></div>
		<h3><?php echo JText::_( 'COM_EASYSTAGING_ABOUT_DATABASE_SYNCING' ); ?></h3>
		<?php echo JText::_( 'COM_EASYSTAGING_DATABASE_SYNCING_DESC' ) ?>
	</div>
</div>
<input type="hidden" name="option" value="com_easystaging" />
<input type="hidden" name="task" value="" />
<input type="hidden" name="boxchecked" value="0" />
<input type="hidden" name="controller" value="easystaging" />
 
</form>
