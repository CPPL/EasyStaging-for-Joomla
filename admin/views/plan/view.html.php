<?php
/**
 * Plan Editor View for EasyStaging Component
 *
 * @link		http://seepeoplesoftware.com
 * @license		GNU/GPL
 * @copyright	Craig Phillips Pty Ltd
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

jimport('joomla.application.component.view');

/**
 * EasyStaging Plan Editor View
 *
 * @property mixed form
 */
class EasyStagingViewPlan extends JView
{
	/* @var $form JForm */
	protected $form;

	protected $item;

	protected $canDo;

	protected $runOnly;

	/**
	 * Execute and display a template script.
	 *
	 * @param   string  $tpl  The name of the template file to parse; automatically searches through the template paths.
	 *
	 * @return  mixed  A string if successful, otherwise a JError object.
	 *
	 * @since   1.0
	 */
	public function display($tpl = null)
	{
		require_once JPATH_COMPONENT . '/helpers/plan.php';

		// Get the Data
		$form = $this->get('Form');
		$item = $this->get('Item');

		// Check for errors.
		if (count($errors = $this->get('Errors')))
		{
			JError::raiseError(500, implode('<br />', $errors));

			return false;
		}

		// Assign the Data
		$this->form  = $form;
		$this->item  = $item;

		// Should we be here?
		$this->canDo = PlanHelper::getActions($item->id);

		// Running or Edit/Creating
		$this->runOnly = $this->_runOnlyMode();

		// Set the toolbar etc
		$this->addToolBar();
		$this->addCSSEtc();

		// Display the template
		parent::display($tpl);
	}

	/**
	 * Add the Toolbar for Plan view.
	 *
	 * @return void
	 */
	private function addToolbar ()
	{
		$jinput = JFactory::getApplication()->input;
		$jinput->set('hidemainmenu', true);
		$canDo	    = $this->canDo;
		$user		= JFactory::getUser();

		$isNew		= ($this->item->id == 0);
		$checkedOut	= !($this->item->checked_out == 0 || $this->item->checked_out == $user->get('id'));

		if ($canDo->get('core.edit') || $canDo->get('core.create'))
		{
			JToolBarHelper::title($isNew ? JText::_('COM_EASYSTAGING_MANAGER_PLAN_NEW') : JText::_('COM_EASYSTAGING_MANAGER_PLAN_EDIT'), 'easystaging');
			JToolBarHelper::apply('plan.apply');
			JToolBarHelper::save('plan.save');
		}
		elseif ($canDo->get('easystaging.run'))
		{
			JToolBarHelper::title(JText::_('COM_EASYSTAGING_MANAGER_PLAN_RUN'), 'easystaging');
		}

		if (!$checkedOut && ($canDo->get('core.create')))
		{
			JToolBarHelper::save2new('plan.save2new');
		}
		JToolBarHelper::cancel('plan.cancel', $isNew ? 'JTOOLBAR_CANCEL' : 'JTOOLBAR_CLOSE');
		JToolBarHelper::divider();
		JToolBarHelper::help('COM_EASYSTAGING_HELP_EASYSTAGING_MANAGER', false, 'http://seepeoplesoftware.com/products/easystaging/1.0/help/plan.html');
	}

	/**
	 * Add the CSS for Plan view.
	 *
	 * @return void
	 */
	private function addCSSEtc ()
	{
		// Get the document object
		$document = JFactory::getDocument();

		// First add CSS to the document
		$document->addStyleSheet(JURI::root() . 'media/com_easystaging/css/plan.css');

		// Load the defaults first so that our script loads after them
		JHtml::_('behavior.framework', true);
		JHtml::_('behavior.tooltip');
		JHtml::_('behavior.multiselect');

		// Then add JS to the document‚ - make sure all JS comes after CSS
		if ($this->item->published)
		{
			// General Tools first
			$jsFile = 'media/com_easystaging/js/atools.js';
			$document->addScript(JURI::root() . $jsFile);
			PlanHelper::loadJSLanguageKeys('/' . $jsFile);
			// View specific
			$jsFile = 'media/com_easystaging/js/plan.js';
			$document->addScript(JURI::root() . $jsFile);
			PlanHelper::loadJSLanguageKeys('/' . $jsFile);
		}

		// Finally is the plan clean, i.e. can it by run?
		if (!$this->item->clean && $this->item->published)
		{
			$lockOutPlanBtnsScript = <<<'JS'
window.addEvent('domready', function () {
	/* Disable Plan Run buttons until it's been saved */
	com_EasyStaging.lockOutBtns(false);
});
JS;

			$document->addScriptDeclaration($lockOutPlanBtnsScript);
		}
	}

	/**
	 * Adjusts the form users with only 'run' permissions.
	 *
	 * @return bool
	 */
	private function _runOnlyMode()
	{
		if (!($this->canDo->get('core.edit') || $this->canDo->get('core.create')) && $this->canDo->get('easystaging.run'))
		{
			// They can run but not hide, I mean create/edit plans - better limit the access to form elements.
			$this->form->setFieldAttribute('name', 'class', 'readonly');
			$this->form->setFieldAttribute('name', 'readonly', 'true');
			$this->form->setFieldAttribute('description', 'class', 'readonly');
			$this->form->setFieldAttribute('description', 'disabled', 'true');
			$this->form->setFieldAttribute('published', 'class', 'readonly');
			$this->form->setFieldAttribute('published', 'readonly', 'true');
			$this->form->setFieldAttribute('publish_up', 'class', 'readonly');
			$this->form->setFieldAttribute('publish_up', 'readonly', 'true');
			$this->form->setFieldAttribute('publish_up', 'format', '%Y-%m-%d %H:%M:%S');
			$this->form->setFieldAttribute('publish_up', 'filter', 'user_utc');
			$this->form->setFieldAttribute('publish_down', 'class', 'readonly');
			$this->form->setFieldAttribute('publish_down', 'readonly', 'true');
			$this->form->setFieldAttribute('publish_down', 'format', '%Y-%m-%d %H:%M:%S');
			$this->form->setFieldAttribute('publish_down', 'filter', 'user_utc');

			// Finally return true for run only mode
			return true;
		}
		else
		{
			return false;
		}
	}

	/**
	 * Generates action menu for tables.
	 *
	 * @param   int     $selectedAction  Current setting for this table.
	 *
	 * @param   string  $controlName     Current table name
	 *
	 * @param   int     $rowId           Row ID of table record.
	 *
	 * @return string
	 */
	protected function _getActionMenu($selectedAction, $controlName, $rowId)
	{
		// Get custom field
		JFormHelper::addFieldPath(JPATH_COMPONENT . '/models/fields');
		$actionChoices2 = JFormHelper::loadFieldType('EasyStagingAction', false);
		$actionOptions = $actionChoices2->getOptions();

		$actionMenu = JHtml::_('select.genericlist', $actionOptions, $controlName, 'class="inputbox"', 'value', 'text', $selectedAction, $rowId);

		return $actionMenu;
	}
}
