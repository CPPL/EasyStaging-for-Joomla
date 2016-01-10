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
		$date = JFactory::getDate()->toSql();
		$uid  = JFactory::getUser()->get('id');

		if ($this->id)
		{
			// Existing item
			if (!isset($this->dry_run) || !$this->dry_run) {
				$this->modified = $date;
				$this->modified_by = $uid;
			}
		}
		else
		{
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
		// Bind the rules.
		if (isset($array['rules']) && is_array($array['rules']))
		{
			$rules = new JAccessRules($array['rules']);
			$this->setRules($rules);
		}
		// And call the parent.
		return parent::bind($array, $ignore);
	}

	/**
	 * Method to delete a row from the database table by primary key value.
	 *
	 * @param   mixed  $pk  An optional primary key value to delete.  If not set the instance property value is used.
	 *
	 * @return  boolean  True on success.
	 *
	 * @link	http://docs.joomla.org/JTable/delete
	 * @since   11.1
	 */
	public function delete($pk = null)
	{
		// We call the parent version first to look after the `plan` records
		if (parent::delete($pk))
		{
			// Initialise variables.
			$k = $this->_tbl_key;
			$pk = (is_null($pk)) ? $this->$k : $pk;

			// If no primary key is given, return false.
			if ($pk === null)
			{
				$e = new JException(JText::_('JLIB_DATABASE_ERROR_NULL_PRIMARY_KEY'));
				$this->setError($e);
				return false;
			}

			// Delete the Plans `table` records by primary key.
			$query = $this->_db->getQuery(true);
			$query->delete();
			$query->from('#__easystaging_tables');
			$query->where('plan_id = ' . $this->_db->quote($pk));
			$this->_db->setQuery($query);

			// Check for a database error.
			if (!$this->_db->query())
			{
				$e = new JException(JText::sprintf('JLIB_DATABASE_ERROR_DELETE_FAILED', get_class($this), $this->_db->getErrorMsg()));
				$this->setError($e);
				return false;
			}

			// Delete the Plans `site` records by primary key.
			$query = $this->_db->getQuery(true);
			$query->delete();
			$query->from('#__easystaging_sites');
			$query->where('plan_id = ' . $this->_db->quote($pk));
			$this->_db->setQuery($query);
			
			// Check for a database error.
			if (!$this->_db->query())
			{
				$e = new JException(JText::sprintf('JLIB_DATABASE_ERROR_DELETE_FAILED', get_class($this), $this->_db->getErrorMsg()));
				$this->setError($e);
				return false;
			}

			return true;
		}
		else
		{
			return false;
		}
	}
	/**
	* Method to compute the default name of the asset.
	* So we can support actions.
	*
	*/
	protected function _getAssetName()
	{
		$k = $this->_tbl_key;
		return 'com_easystaging.plan.'.(int) $this->$k;
	}
	
	/**
	 * Method to return the title to use for the asset table.
	 *
	 * @return	string
	 * @since	1.7
	 */
	protected function _getAssetTitle()
	{
		return $this->name;
	}
	
	/**
	 * Get the parent asset id for the record
	 *
	 * @return	int
	 * @since	1.7
	 */
	protected function _getAssetParentId(JTable $table = null, $id = null)
	{
		$asset = JTable::getInstance('Asset');
		$asset->loadByName('com_easystaging');
		return $asset->id;
	}
}
