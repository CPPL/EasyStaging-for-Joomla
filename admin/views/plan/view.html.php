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
 
jimport( 'joomla.application.component.view' );
 
/**
 * EasyStaging Plan Editor View
 *
 */
class EasyStagingViewPlan extends JView
{
	/**
	 * display method of Plan view.
	 * @return void
	 */
	function display($tpl = null)
	{
		require_once JPATH_COMPONENT.'/helpers/plan.php';
		// get the Data
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
		$this->canDo = PlanHelper::getActions($this->item->id);
 
		// Set the toolbar etc
		$this->addToolBar();
		$this->addCSSEtc();

		// Create the action choices array
		$this->assign('actionChoices',$this->_actionChoices());
 
		// Display the template
		parent::display($tpl);
	}

	/**
	 * Add the Toolbar for Plan view.
	 * @return void
	 */
	private function addToolbar ()
	{
		JRequest::setVar('hidemainmenu', true);
		$canDo	    = $this->canDo;
		$user		= JFactory::getUser();

		$isNew		= ($this->item->id == 0);
		$checkedOut	= !($this->item->checked_out == 0 || $this->item->checked_out == $user->get('id'));
		
		if($canDo->get('easystaging.edit') || $canDo->get('easystaging.create')) {
			JToolBarHelper::title($isNew ? JText::_('COM_EASYSTAGING_MANAGER_PLAN_NEW') : JText::_('COM_EASYSTAGING_MANAGER_PLAN_EDIT'), 'easystaging');
			JToolBarHelper::apply('plan.apply');
			JToolBarHelper::save('plan.save');
		} elseif($canDo->get('easystaging.run')) {
			JText::_('COM_EASYSTAGING_MANAGER_PLAN_RUN');
		}
		                             
		if (!$checkedOut && ($canDo->get('easystaging.create'))) {
			JToolBarHelper::save2new('plan.save2new');
		}
		JToolBarHelper::cancel('plan.cancel', $isNew ? 'JTOOLBAR_CANCEL' : 'JTOOLBAR_CLOSE');
		JToolBarHelper::divider();
		JToolBarHelper::help('COM_EASYSTAGING_HELP_EASYSTAGING_MANAGER',false,'http://seepeoplesoftware.com/products/easystaging/1.0/help/plan.html');
	}

	/**
	 * Add the CSS for Plan view.
	 * @return void
	 */
	private function addCSSEtc ()
	{
		// Get the document object
		$document = &JFactory::getDocument();
		
		// First add CSS to the document
		$document->addStyleSheet('/administrator/components/com_easystaging/assets/css/plan.css');
		
		// Load the defaults first so that our script loads after them
		JHtml::_('behavior.framework', true);
		JHtml::_('behavior.tooltip');
		JHtml::_('behavior.multiselect');
		
		// Then add JS to the document‚ - make sure all JS comes after CSS
		$jsFile = '/assets/js/plan.js';
		$document->addScript(JURI::base(true).'/components/com_easystaging'.$jsFile);
		$this->_loadJSLanguageKeys($jsFile);
	}

	private  function _loadJSLanguageKeys($jsFile) {
		if(isset($jsFile))
		{
			$jsFile = JPATH_COMPONENT_ADMINISTRATOR.$jsFile;
		} else {
			return false;
		}
		
		if($jsContents = file_get_contents($jsFile))
		{
			$languageKeys = array();
			preg_match_all('/Joomla\.JText\..*\)?/', $jsContents, $languageKeys);
			$languageKeys = $languageKeys[0];
			foreach ($languageKeys as $lkey) {
				$lkeyArray = explode('\'', $lkey);
				$this_lkey = $lkeyArray[1];
				JText::script($this_lkey);
			}
		}
	}

	private function _actionChoices()
	{
		$actionChoices = array( );
		$actionChoices[] = array('action' => 0, 'actionLabel' => '-- '.JText::_('COM_EASYSTAGING_TABLE_ACTION0').' --');
		$actionChoices[] = array('action' => 1, 'actionLabel' => JText::_('COM_EASYSTAGING_TABLE_ACTION1'));
		$actionChoices[] = array('action' => 2, 'actionLabel' => JText::_('COM_EASYSTAGING_TABLE_ACTION2'));
		$actionChoices[] = array('action' => 3, 'actionLabel' => JText::_('COM_EASYSTAGING_TABLE_ACTION3'));
		$actionChoices[] = array('action' => 4, 'actionLabel' => JText::_('COM_EASYSTAGING_TABLE_ACTION4'));
		$actionChoices[] = array('action' => 5, 'actionLabel' => JText::_('COM_EASYSTAGING_TABLE_ACTION5'));
		return $actionChoices;
	}
}
