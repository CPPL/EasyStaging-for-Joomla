<?php
defined('_JEXEC') or die;
?>
<!-- Main Form Body -->
<div class="width-60 fltlft">
<fieldset class="adminform">
<legend><?php echo JText::_( 'COM_EASYSTAGING_PLAN_DETAILS' ); ?></legend>
		<ul class="adminformlist">
			<li><?php echo $this->form->getLabel('name'); ?> <?php echo $this->form->getInput('name'); ?></li>
			<li><?php echo $this->form->getLabel('description'); ?> <?php echo $this->form->getInput('description'); ?></li>
			<li><?php echo $this->form->getLabel('published'); ?> <?php echo $this->form->getInput('published'); ?></li>
			<li><?php echo $this->form->getLabel('id'); ?> <?php echo $this->form->getInput('id'); ?></li>

		<?php if ($this->canDo->get('core.admin')): ?>
			<li><?php echo $this->form->getLabel('access'); ?>
			<?php echo $this->form->getInput('access'); ?></li>
			<li><span class="faux-label"><?php echo JText::_('JGLOBAL_ACTION_PERMISSIONS_LABEL'); ?></span>
				<div class="button2-left"><div class="blank">
					<button type="button" onclick="document.location.href='#access-rules';">
						<?php echo JText::_('JGLOBAL_PERMISSIONS_ANCHOR'); ?>
					</button>
				</div></div>
			</li>
		<?php endif; ?>
		</ul>
	</fieldset>
</div>

<!-- Parameter Sidebar -->
<div class="width-40 fltrt">
	<?php echo JHtml::_('sliders.start','content-sliders-'.$this->item->id, array('useCookie'=>1)); ?>
	<?php echo JHtml::_('sliders.panel',JText::_('COM_EASYSTAGING_BASIC_ATTRIBUTES_LABEL'), 'basic-options'); ?>
		<fieldset class="panelform">
			<ul class="adminformlist">
				<li><?php	echo $this->form->getLabel('created_by');
							echo $this->form->getInput('created_by');   ?></li>

				<li><?php	echo $this->form->getLabel('created');
							echo $this->form->getInput('created');      ?></li>

				<li><?php 	echo $this->form->getLabel('publish_up');
							echo $this->form->getInput('publish_up');   ?></li>

				<li><?php	echo $this->form->getLabel('publish_down');
							echo $this->form->getInput('publish_down'); ?></li>

				<?php if ($this->item->modified_by) : ?>
				<li><?php	echo $this->form->getLabel('modified_by');
							echo $this->form->getInput('modified_by'); ?></li>
				<li><?php	echo $this->form->getLabel('modified');
							echo $this->form->getInput('modified');    ?></li>
				<?php endif; ?>
			</ul>
		</fieldset>
	<?php echo JHtml::_('sliders.panel',JText::_('COM_EASYSTAGING_NOTES_LABEL'), 'notes'); ?>
	<fieldset class="panelform">
		<legend><br><?php echo JText::_('COM_EASYSTAGING_NOTES_TABLE_LABEL'); ?></legend>
		<ul>
			<li><strong><?php echo JText::_('COM_EASYSTAGING_TABLE_ACTION0'); ?></strong><br />
				<?php echo JText::_('COM_EASYSTAGING_TABLE_ACTION0_DESC'); ?></li>
			<li><strong><?php echo JText::_('COM_EASYSTAGING_TABLE_ACTION1'); ?></strong><br />
				<?php echo JText::_('COM_EASYSTAGING_TABLE_ACTION1_DESC'); ?></li>
			<li><strong><?php echo JText::_('COM_EASYSTAGING_TABLE_ACTION2'); ?></strong><br />
				<?php echo JText::_('COM_EASYSTAGING_TABLE_ACTION2_DESC'); ?></li>
			<li><strong><?php echo JText::_('COM_EASYSTAGING_TABLE_ACTION3'); ?></strong><br />
				<?php echo JText::_('COM_EASYSTAGING_TABLE_ACTION3_DESC'); ?></li>
			<li><strong><?php echo JText::_('COM_EASYSTAGING_TABLE_ACTION4'); ?></strong><br />
				<?php echo JText::_('COM_EASYSTAGING_TABLE_ACTION4_DESC'); ?></li>
			<li><strong><?php echo JText::_('COM_EASYSTAGING_TABLE_ACTION5'); ?></strong><br />
				<?php echo JText::_('COM_EASYSTAGING_TABLE_ACTION5_DESC'); ?><br />
			</li>
		</ul>
		<legend><br><?php echo JText::_('COM_EASYSTAGING_NOTES_RSYNC_LABEL'); ?></legend>
		<ul>
			<li><?php echo JText::_('COM_EASYSTAGING_NOTES_RSYNC_DESC'); ?></li>
		</ul>
	</fieldset>
	<?php echo JHtml::_('sliders.end'); ?>
</div>

<div class="clr"></div>
