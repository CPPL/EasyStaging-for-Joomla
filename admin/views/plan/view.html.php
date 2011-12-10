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
		$this->form = $form;
		$this->item = $item;
 
		// Set the toolbar etc
		$this->addToolBar();
		$this->addCSSEtc();
 
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
		$user		= JFactory::getUser();
		$isNew		= ($this->item->id == 0);
		$checkedOut	= !($this->item->checked_out == 0 || $this->item->checked_out == $user->get('id'));
		
		JToolBarHelper::title($isNew ? JText::_('COM_EASYSTAGING_MANAGER_PLAN_NEW')
		                             : JText::_('COM_EASYSTAGING_MANAGER_PLAN_EDIT'));
		JToolBarHelper::apply('plan.apply');
		JToolBarHelper::save('plan.save');
			if (!$checkedOut && (count($user->getAuthorisedCategories('com_easystaging', 'core.create')))){
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
		
		// Then add JS to the document‚ - make sure all JS comes after CSS
		$document->addScript('/administrator/components/com_easystaging/assets/js/plan.js');
	}
}
