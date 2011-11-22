<?php
/**
 * @package		EasyStaging
 * @link		http://seepeoplesoftware.com
 * @license		GNU/GPL
 * @copyright	Craig Phillips Pty Ltd
*/
 
// No direct access
 
defined( '_JEXEC' ) or die( 'Restricted access' );
 
// Access check.
if (!JFactory::getUser()->authorise('core.manage', 'com_easystaging')) {
	return JError::raiseWarning(404, JText::_('JERROR_ALERTNOAUTHOR'));
}

// Include dependencies
jimport('joomla.application.component.controller');

$controller = JController::getInstance('EasyStaging');

$controller->execute(JRequest::getCmd('task'));
 
// Redirect if set by the controller
$controller->redirect();
