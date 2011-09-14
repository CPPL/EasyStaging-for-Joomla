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
        JToolBarHelper::title( JText::_( 'EasyStaging Manager' ), 'generic.png' );
        JToolBarHelper::deleteList();
        JToolBarHelper::editListX();
        JToolBarHelper::addNewX();
 
        // Get data from the model
        $items =& $this->get( 'Data');
 
        $this->assignRef( 'items', $items );
 
        parent::display($tpl);
    }
}
