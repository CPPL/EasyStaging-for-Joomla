<?php
// No direct access
defined('_JEXEC') or die('Restricted access');
 
// import Joomla table library
jimport('joomla.database.table');

// Load our run helper
if (file_exists(JPATH_COMPONENT_ADMINISTRATOR . '/helpers/run.php'))
{
	require_once JPATH_COMPONENT_ADMINISTRATOR . '/helpers/run.php';
}
else
{
	die("EasyStaging isn't installed correctly.");
}

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
		parent::__construct('#__easystaging_tables', 'id', $db);
	}

	/**
	 * Method to perform sanity checks on the JTable instance properties to ensure
	 * they are safe to store in the database.  Child classes should override this
	 * method to make sure the data they are storing in the database is safe and
	 * as expected before storage.
	 *
	 * @return  boolean  True if the instance is sane and able to be stored in the database.
	 *
	 * @link    http://docs.joomla.org/JTable/check
	 * @since   11.1
	 */
	public function check()
	{
		// Assume success, i.e. most tables have an index
		$checkResult = true;

		$config = JFactory::getConfig();
		$dbName = $config->get('db');
		$db = $this->getDbo();

		// Make sure the action is compatible with the table, e.g. merges require a PK and not all tables have an index.
		// Now we need the primary key for the table...
		$table = $this->tablename;
		$action = $this->action;

		// Range doesn't work with CONST - seriously... WTF? (Note these are raw actions from the UI)
		$mergeActions = range(3, 5);

		if (in_array($action, $mergeActions))
		{
			$pkSQL = RunHelper::buildTablePkQuery($dbName, $table);
			$db->setQuery($pkSQL);
			$tableProfile = $db->loadAssoc();

			if ($tableProfile == null)
			{
				$this->action = 0;
				JFactory::getApplication()->enqueueMessage(JText::sprintf('COM_EASYSTAGING_TABLE_MERGE_ACTION_NOT_VALID_FOR_X', $table), 'Notice');
			}
		}

		return $checkResult;
	}
}
