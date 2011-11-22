<?php
/**
 * @package		EasyStaging
 * @link		http://seepeoplesoftware.com
 * @license		GNU/GPL
 * @copyright	Craig Phillips Pty Ltd
*/
 
// No direct access
 
defined( '_JEXEC' ) or die( 'Restricted access' );
 
jimport('joomla.application.component.controller');
 
/**
 * EasyStaging Component Controller
 */
class EasyStagingController extends JController
{
    /**
     * Method to display the view
     *
     * @access    public
     */
    function display($cachable = false)
    {	// Set the default view if required
		JRequest::setVar('view', JRequest::getCmd('view', 'Plans'));

    	parent::display($cachable);
    }
 
}
