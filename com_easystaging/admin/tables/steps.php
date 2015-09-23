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

	/**
	 * Refresh the current row, useful in long running processes that update a given row.
	 *
	 * @return  bool
	 */
	public function refresh()
	{
		$keyName = $this->_tbl_key;
		$keyValue = $this->$keyName;

		return $this->load($keyValue);
	}
}
