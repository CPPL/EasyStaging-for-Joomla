<?php
defined('_JEXEC') or die;
?>
<!-- Main Form Body -->
<div class="span9">
	<fieldset class="form-vertical">
<?php
		echo $this->form->getControlGroup('description');

		if ($this->runOnly)
		{
			$this->form->setFieldAttribute('easytablealias', 'class', 'readonly');
			$this->form->setFieldAttribute('easytablealias', 'readonly', 'true');
		}
?>
	</fieldset>
</div>

<!-- Parameter Sidebar -->
<div class="span3">
	<fieldset class="form-vertical">
		<?php
		echo $this->form->getControlGroup('published');
		echo $this->form->getControlGroup('created_by');
		echo $this->form->getControlGroup('created');
		echo $this->form->getControlGroup('publish_up');
		echo $this->form->getControlGroup('publish_down');

		if ($this->canDo->get('core.admin'))
		{
			echo $this->form->getControlGroup('access');
		}

		if ($this->item->modified_by)
		{
			echo $this->form->getControlGroup('modified_by');
			echo $this->form->getControlGroup('modified');
		}
		?>
	</fieldset>
</div>

<div class="clr"></div>
