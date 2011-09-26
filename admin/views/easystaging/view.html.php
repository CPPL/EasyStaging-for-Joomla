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
class EasyStagingViewEasyStaging extends JView
{
	function display($tpl = null)
	{
		// Setup document (Toolbar, css, js etc)
		$this->addToolbar();
		$this->addCSSEtc();

		// Get data from the model
		$items =& $this->get( 'Data');
		
		$this->assignRef( 'items', $items );
		
		parent::display($tpl);
	}

	private function addToolbar ()
	{
		JToolBarHelper::title( JText::_( 'EasyStaging Manager' ), 'generic.png' );
		JToolBarHelper::publishList();
		JToolBarHelper::deleteList();
		JToolBarHelper::editListX();
		JToolBarHelper::addNewX();
	}
	
	private function addCSSEtc ()
	{
		// Get the document object
		$document = &JFactory::getDocument();
		
		// First add CSS to the document
		$document->addStyleSheet(JURI::base().'components/com_easystaging/assets/css/easystaging.css');
		
		// Then add JS to the document ‚ - make sure all JS comes after CSS
		//$document->addScript('templates/' . $this->template . '/js/jquery-1.4.4.min.js');
	}
}
