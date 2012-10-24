<?php
/**
 * Main Manager View for EasyStaging Component
 * 
 * @link		http://seepeoplesoftware.com
 * @license		GNU/GPL
 * @copyright	Craig Phillips Pty Ltd
 */
 
// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();
 
jimport( 'joomla.application.component.view' );
 
/**
 * EasyStaging Manager View
 *
 */
class EasyStagingViewPlans extends JView
{
	function display($tpl = null)
	{
		require_once JPATH_COMPONENT.'/helpers/plan.php';

		JHtml::_('behavior.framework', true);
		JHtml::_('behavior.tooltip');
		JHtml::_('behavior.multiselect');

		// Setup document (Toolbar, css, js etc)
		$this->addToolbar();
		$this->addCSSEtc();

		// Get data from the model
		$items = $this->get( 'Items');
		$pagination = $this->get('Pagination');
		
		if (count($errors = $this->get('Errors'))) 
		{
			JError::raiseError(500, implode('<br />', $errors));
			return false;
		}
		// Assign data to the view
		$this->items = $items;
		$this->pagination = $pagination;
		
		parent::display($tpl);
	}

	private function addToolbar ()
	{
		JToolBarHelper::title( JText::_( 'COM_EASYSTAGING_EASYSTAGING_MANAGER' ), 'easystaging' );

		$canDo	= PlanHelper::getActions();
		$user	= JFactory::getUser();

		if ($canDo->get('core.create'))
		{
			JToolBarHelper::addNew('plan.add');
		}
		
		if ($canDo->get('core.edit'))
		{
			JToolBarHelper::editList('plan.edit');
		}

		if ($canDo->get('core.edit.state'))
		{
			JToolBarHelper::divider();
			JToolBarHelper::publishList('plans.publish', 'JTOOLBAR_PUBLISH', true);
			JToolBarHelper::unpublishList('plans.unpublish', 'JTOOLBAR_UNPUBLISH', true);
		}

		if ($canDo->get('core.delete'))
		{
			JToolBarHelper::deleteList('','plans.delete');
			JToolBarHelper::divider();
		}

		if ($canDo->get('core.admin'))
		{
			JToolBarHelper::preferences('com_easystaging');
			JToolBarHelper::divider();
		}

		JToolBarHelper::help(	'COM_EASYSTAGING_HELP_EASYSTAGING_MANAGER',
								false,
								'http://seepeoplesoftware.com/products/easystaging/1.0/help/plans.html');
	}
	
	private function addCSSEtc ()
	{
		// Get the document object
		$document = JFactory::getDocument();
		
		// First add CSS to the document
		$document->addStyleSheet(JURI::root().'media/com_easystaging/css/plans.css');
		
		// Then add JS to the document‚ - make sure all JS comes after CSS
		$jsFile = 'media/com_easystaging/js/plans.js';
		$document->addScript(JURI::root().$jsFile);
		PlanHelper::loadJSLanguageKeys('/'.$jsFile);
	}
}
