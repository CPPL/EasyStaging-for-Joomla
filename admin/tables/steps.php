<?php

// No direct access
defined('_JEXEC') or die('Restricted access');

// Import Joomla table library
jimport('joomla.database.table');
 
/**
 * Steps Table class
 */
class EasyStagingTableSteps extends JTable
{
	/**
	 * Constructor
	 *
	 * @param   object  &$db  Database connector object
	 */
	public function __construct(&$db)
	{
		parent::__construct('#__easystaging_steps', 'id', $db);
	}
}
