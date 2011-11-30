<?php
// No direct access
defined('_JEXEC') or die('Restricted access');
 
// import Joomla table library
jimport('joomla.database.table');
 
/**
 * Hello Table class
 */
class EasyStagingTablePlan extends JTable
{
	/**
	 * Constructor
	 *
	 * @param object Database connector object
	 */
	function __construct(&$db) 
	{
		parent::__construct('#__easystaging_plans', 'id', $db);
	}

	/**
	 * Overriden store method to set dates.
	 *
	 * @param	boolean	True to update fields even if they are null.
	 *
	 * @return	boolean	True on success.
	 * @see		JTable::store
	 * @since	1.6
	 */
	public function store($updateNulls = false)
	{
		// Initialise variables.
		$date = JFactory::getDate()->toMySQL();
		$uid  = JFactory::getUser()->get('id');

		if ($this->id) {
			// Existing item
			$this->modified = $date;
			$this->modified_by = $uid;
		} else {
			// New record.
			$this->created = $date;
			$this->created_by = $uid;
		}

		return parent::store($updateNulls);
	}

	/**
	* Overloaded bind function.
	*
	* @todo    Bind (& store) the site and table tables to their relevant fields from the form.
	* @return  mixed  Null if operation was satisfactory, otherwise returns an error
	*
	* @see     JTable:bind
	* @since   11.1
	*/
	public function bind($array, $ignore='')
	{
		// In here we also bind the secondary table information.
	
		// For now just call the parent.
		return parent::bind($array, $ignore);
	}

	/**
	 * Method to load a row from the database by primary key and bind the fields
	 * to the JTable instance properties.
	 *
	 * @param   mixed    $keys   An optional primary key value to load the row by, or an array of fields to match.  If not
	 *                           set the instance property value is used.
	 * @param   boolean  $reset  True to reset the default values before loading the new row.
	 *
	 * @return  boolean  True if successful. False if row not found or on error (internal error state set in that case).
	 *
	 * @link    http://docs.joomla.org/JTable/load
	 * @since   11.1
	 */
	public function load($keys = null, $reset = true)
	{
		
		return parent::load($keys, $reset);
	}
}
