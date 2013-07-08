<?php
// No direct access
defined('_JEXEC') or die('Restricted access');
 
// import Joomla table library
jimport('joomla.database.table');

/**
 * Hello Table class
 */
class EasyStagingTableTables extends JTable
{
	/**
	 * Constructor
	 *
	 * @param   JDatabase  &$db  DB connector object
	 */
	public function __construct(&$db)
	{
		parent::__construct('#__easystaging_rsyncs', 'id', $db);
	}
}
