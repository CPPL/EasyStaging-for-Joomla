<?php
// No direct access
defined('_JEXEC') or die('Restricted access');
JHtml::_('behavior.tooltip');
// Load the tooltip behavior.
JHtml::_('behavior.tooltip');
JHtml::_('behavior.formvalidation');
JHtml::_('behavior.keepalive');
?>

<script type="text/javascript">
	Joomla.submitbutton = function(task) {
		if (task == 'plan.cancel' || document.formvalidator.isValid(document.id('easystaging-form'))) {
			Joomla.submitform(task, document.getElementById('easystaging-form'));
		} else {
			alert('<?php echo $this->escape(JText::_('JGLOBAL_VALIDATION_FORM_FAILED'));?>');
		}
	}
</script>

<form action="<?php echo JRoute::_('index.php?option=com_easystaging&layout=edit&id='.(int) $this->item->id); ?>"
      method="post" name="adminForm" id="easystaging-form">
<!-- Main Form Body -->
	<div class="width-60 fltlft">
		<fieldset class="adminform">
			<legend><?php echo JText::_( 'COM_EASYSTAGING_PLAN_DETAILS' ); ?></legend>
			<ul class="adminformlist">
				<li><?php echo $this->form->getLabel('name'); ?> <?php echo $this->form->getInput('name'); ?></li>
				<li><?php echo $this->form->getLabel('description'); ?> <?php echo $this->form->getInput('description'); ?></li>
				<li><?php echo $this->form->getLabel('published'); ?> <?php echo $this->form->getInput('published'); ?></li>
				<li><?php echo $this->form->getLabel('id'); ?> <?php echo $this->form->getInput('id'); ?></li>
<?php
/***
 * @todo Set Permssions button will go here 
 */
?>
			</ul>
		</fieldset>
	</div>

<!-- Parameter Sidebar -->
	<div class="width-40 fltrt">
		<?php echo JHtml::_('sliders.start','content-sliders-'.$this->item->id, array('useCookie'=>1)); ?>
		<?php echo JHtml::_('sliders.panel',JText::_('COM_EASYSTAGING_BASIC_ATTRIBUTES_LABEL'), 'basic-options'); ?>
			<fieldset class="panelform">
				<ul class="adminformlist">
					<li><?php echo $this->form->getLabel('created_by'); ?> <?php echo $this->form->getInput('created_by'); ?></li>
	
					<li><?php echo $this->form->getLabel('created'); ?> <?php echo $this->form->getInput('created'); ?></li>
	
					<li><?php echo $this->form->getLabel('publish_up'); ?> <?php echo $this->form->getInput('publish_up'); ?></li>
	
					<li><?php echo $this->form->getLabel('publish_down'); ?> <?php echo $this->form->getInput('publish_down'); ?></li>
	
					<?php if ($this->item->modified_by) : ?>
						<li><?php echo $this->form->getLabel('modified_by'); ?> <?php echo $this->form->getInput('modified_by'); ?></li>
						<li><?php echo $this->form->getLabel('modified'); ?> <?php echo $this->form->getInput('modified'); ?></li>
					<?php endif; ?>
				</ul>
			</fieldset>
		<?php echo JHtml::_('sliders.end'); ?>
	</div>

	<div class="clr"></div>

<!-- Permissions UI -->
		<div class="width-100 fltlft">
<?php
/***
 * @todo Permssions UI will go here 
 */
?>
		<h3>Access control will go here...</h3>
	</div>

	<div>
		<input type="hidden" name="task" value="plan.edit" />
		<?php echo JHtml::_('form.token'); ?>
	</div>
</form>