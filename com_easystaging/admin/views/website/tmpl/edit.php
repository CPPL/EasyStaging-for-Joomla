<?php
// No direct access
defined('_JEXEC') or die('Restricted access');

// Load the tooltip behavior.
JHtml::_('behavior.formvalidation');
JHtml::_('formbehavior.chosen', 'select');

if (!$this->canDo->get('core.edit'))
{
    $app =& JFactory::getApplication();
    $app->redirect('index.php?option=com_easystaging');

    return JError::raiseWarning(404, JText::_('JERROR_ALERTNOAUTHOR'));
}
/* @var JForm $form */
$form = $this->form;
$formAction = JRoute::_('index.php?option=com_easystaging&layout=edit&id=' . (int) $this->item->id);
?>
<form action="<?php echo $formAction ?>" method="post" name="adminForm" id="easystaging-form">
    <div class="span12">
        <div class="form-inline form-inline-header">
            <?php
            echo $this->form->renderField('site_name', null);
            $form->setFieldAttribute('id', 'class', 'readonly');
            $form->setFieldAttribute('id', 'readonly', 'true');
            echo $form->renderField('id');
            ?>
        </div>
        <?php
        if ($this->canDo->get('core.edit') || ($this->canDo->get('core.create') && ($this->item->id == 0)))
        {
            echo $this->loadTemplate('form');
        }
        ?>
        <input type="hidden" id="id" name="id" value="<?php echo $this->item->id; ?>">
        <input type="hidden" name="task" value="plan.edit" />
        <?php echo JHtml::_('form.token'); ?>
    </div>

</form>
