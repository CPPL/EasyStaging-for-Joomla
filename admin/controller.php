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

jimport('joomla.application.component.controller');

/**
 * Class EasyStagingController
 *
 * @package  EasyStaging
 *
 * @since    1.0
 */
class EasyStagingController extends JController
{
	/**
     * Our version of the default display()
     *
     * @param   boolean        $cachable   If true, the view output will be cached
     *
     * @param   boolean|array  $urlparams  An array of safe url parameters and their variable types.
     *
     * @return  JController  A JController object to support chaining.
     */
	public function display($cachable = false, $urlparams =  false)
	{
		// Set the default view if required
		$jIn = JFactory::getApplication()->input;

		// JRequest::setVar('view', JRequest::getCmd('view', 'Plans'));
		$jIn->set('view', $jIn->getCmd('view', 'Plans'));

		parent::display($cachable);
	}
}
