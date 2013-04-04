<?php
/**
 * @package    EasyStaging
 * @author     Craig Phillips <craig@craigphillips.biz>
 * @copyright  Copyright (C) 2012 Craig Phillips Pty Ltd.
 * @license    GNU General Public License version 3, or later
 * @url        http://www.seepeoplesoftware.com
 *
 */
 
// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();
 
jimport( 'joomla.application.component.modellist' );
 
/**
 * EasyStaging Model
 *
 */
class EasyStagingModelSteps extends JModel
{
	/**
	 * Method to load the plan run steps and massage into a plan run object.
	 *
	 * @return	string	An SQL query
	 */
	protected function getPlanRun()
	{
		// Create a new query object.
		$db = JFactory::getDBO();
		$query = $db->getQuery(true);

		// Select some fields
		$query->select('*');

		// Where our run ticket matches

		// From the EasyStaging steps table
		$query->from('#__easystaging_steps');

		// Order by run plan key and step ID (as in theory steps are created in the order they are to run in.
		$query->order(array('runticket', 'id'));


		return $query;
	}
}
