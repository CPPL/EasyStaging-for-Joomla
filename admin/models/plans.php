<?php
/**
 * EasyStaging Model for EasyStaging Component
 * @link		http://seepeoplesoftware.com
 * @license		GNU/GPL
 * @copyright	Craig Phillips Pty Ltd
 */
 
// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();
 
jimport( 'joomla.application.component.modellist' );
 
/**
 * EasyStaging Model
 *
 */
class EasyStagingModelPlans extends JModelList
{
	/**
	 * Method to build an SQL query to load the list data.
	 *
	 * @return	string	An SQL query
	 */
	protected function getListQuery()
	{
		// Create a new query object.
		$db = JFactory::getDBO();
		$query = $db->getQuery(true);

		// Select some fields
		$query->select($db->quoteName('id'));
		$query->select($db->quoteName('name'));
		$query->select($db->quoteName('description'));
		$query->select($db->quoteName('published'));
		$query->select($db->quoteName('created_by'));
		$query->select($db->quoteName('publish_down'));
		$query->select($db->quoteName('publish_up'));
		$query->select($db->quoteName('checked_out'));
		$query->select($db->quoteName('last_run'));

		// From the EasyStaging table
		$query->from('#__easystaging_plans');

		return $query;
	}
}
