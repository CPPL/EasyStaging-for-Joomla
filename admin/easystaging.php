<?php
/**
 * @package    EasyStaging
 * @author     Craig Phillips <craig@craigphillips.biz>
 * @copyright  Copyright (C) 2012 Craig Phillips Pty Ltd.
 * @license    GNU/GPL http://www.gnu.org/copyleft/gpl.html
 * @url        http://www.seepeoplesoftware.com
*/

// No direct access

defined('_JEXEC') or die('Restricted access');

// Access check.
if (!JFactory::getUser()->authorise('core.manage', 'com_easystaging'))
{
	return JError::raiseWarning(404, JText::_('JERROR_ALERTNOAUTHOR'));
}

// Include dependencies
jimport('joomla.application.component.controller');

$controller = JController::getInstance('EasyStaging');

$jinput = JFactory::getApplication()->input;

$controller->execute($jinput->get('task'));
 
// Redirect if set by the controller
$controller->redirect();
