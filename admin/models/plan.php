<?php
/**
 * EasyStaging Model for EasyStaging Component
 * @link		http://seepeoplesoftware.com
 * @license		GNU/GPL
 * @copyright	Craig Phillips Pty Ltd
 */
 
// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();
// import Joomla modelform library
jimport('joomla.application.component.modeladmin');
 
/**
 * EasyStaging Plan Model
 *
 */
class EasyStagingModelPlan extends JModelAdmin
{
	/**
	 * Returns a reference to the a Table object, always creating it.
	 *
	 * @param	type	The table type to instantiate
	 * @param	string	A prefix for the table class name. Optional.
	 * @param	array	Configuration array for model. Optional.
	 * @return	JTable	A database object
	 * @since	1.6
	 */
	public function getTable($type = 'Plan', $prefix = 'EasyStagingTable', $config = array()) 
	{
		return JTable::getInstance($type, $prefix, $config);
	}
	/**
	 * Method to get the record form.
	 *
	 * @param	array	$data		Data for the form.
	 * @param	boolean	$loadData	True if the form is to load its own data (default case), false if not.
	 * @return	mixed	A JForm object on success, false on failure
	 * @since	1.6
	 */
	public function getForm($data = array(), $loadData = true) 
	{
		// Get the form.
		$form = $this->loadForm('com_easystaging.plan', 'plan', array('control' => 'jform', 'load_data' => $loadData));
		if (empty($form)) 
		{
			return false;
		}
		return $form;
	}

	/**
	 * Method to get the data that should be injected in the form.
	 *
	 * @return	mixed	The data for the form.
	 * @since	1.6
	 */
	protected function loadFormData() 
	{
		// Check the session for previously entered form data.
		$data = JFactory::getApplication()->getUserState('com_easystaging.edit.plan.data', array());
		if (empty($data)) 
		{
			$data = $this->getItem();
		}
		return $data;
	}

	/**
	 * Method to get a single record.
	 *
	 * @param	integer	The id of the primary key.
	 *
	 * @return	mixed	Object on success, false on failure.
	 */
	public function getItem($pk = null)
	{
		$this->setState('easystaging.clean', 1);

		if ($item = parent::getItem($pk)) {
			$plan_id = intval($item->id);
			
			// Get the sites table
			$Sites = JTable::getInstance('Site', 'EasyStagingTable');
			// Get the local site settings
			$localSite = $Sites->load(array('plan_id'=>$plan_id, 'type'=>'1'));
			if($localSite) {
				$localSite = $Sites->getProperties(); // We need it in an array
			} else {
				// No local site!
				$localSite = $this->_getDefaultValuesFromLocal()->getProperties();
				$localSite['site_url'] = JURI::root();
				$localSite['site_path'] = JPATH_BASE;
				$localSite['rsync_options'] = '-avr';
				// In the odd event that a plan exists but has NO local site data we'll let the user know.
				if(count($this->_forms) && !($plan_id == 0)) // As getItem() gets called twice (first in getForm) we only need to tell the user once the form exists.
				{
					JFactory::getApplication()->enqueueMessage( JText::sprintf('COM_EASYSTAGING_NO_LOCAL_SITE_FOUND_FOR_PLAN', $plan_id) );
				}
			}

			// Get the Tables for local site.
			$localTables = $this->_getExistingTables($plan_id);

			$item->localTables = $localTables;
			$item->localSite = $localSite;

			// Get the remote site settings
			$remoteSite = $Sites->load(array('plan_id'=>$plan_id, 'type'=>'2'));
			if($remoteSite) {
				$remoteSite = $Sites->getProperties();
			} else {
				// No remote site! Get some defaults
				$remoteSite = $this->_getDefaultValuesFromLocal()->getProperties();
				$remoteSite['site_url'] = 'http://';
				$remoteSite['site_path'] = 'public_html/';
				$remoteSite['database_host'] = 'name.ofLiveServer.com';
				if(count($this->_forms) && !($plan_id == 0)) // In the odd event that a plan exists but has NO remote site data we'll let the user know.
				{
					$this->setState('easystaging.clean', 0);
					JFactory::getApplication()->enqueueMessage( JText::sprintf('COM_EASYSTAGING_NO_REMOTE_SITE_FOUND_FOR_PLAN', $plan_id) );
				}
			}

			$item->remoteSite = $remoteSite;
		}

		if(count($this->_forms) && !$this->getState('easystaging.clean',1))
		{
			$mq = JFactory::getApplication()->getMessageQueue();
			if(count($mq))
			{
				JFactory::getApplication()->enqueueMessage( JText::sprintf('COM_EASYSTAGING_SAVE_PLAN_BEFORE_USING', $plan_id) );
				$this->setState('easystaging.clean', 1);
			}
		}

		return $item;
	}

	/*
	 * Over-ride the save so we can store the additional tables at the same time.
	 * 
	 * @param boolean true on success or false on failure
	 * 
	 */
	public function save($data)
	{
		$data['tableSettings'] = JRequest::getVar('tableSettings', array(), 'post', 'array');
		
		if (parent::save($data)) {
			// Check to see if it's a new Plan
			if($this->getState('plan.new',1))
			{	// Update the 'id' in $data to the newly saved Plan's id.
				$data['id'] = $this->getState('plan.id',0);
			}
			
			// Store the localSite record
			$localSiteResult = $this->_saveSiteData($data['id'], $data['localSite'], 1);
			
			// Store the remoteSite record
			$remoteSiteResult = $this->_saveSiteData($data['id'], $data['remoteSite'], 2);
			
			// Store the table settings
			$tableSettingsResult = $this->_saveTableSettings($data['id'], $data['tableSettings']);
			
			if($localSiteResult && $remoteSiteResult && $tableSettingsResult)
			{
				return true;
			} else {
				if(!$localSiteResult)     $this->setError(JText::_('COM_EASYSTAGING_PLAN_UPDATE_LOCALSITE_FAILED'));
				if(!$remoteSiteResult)    $this->setError(JText::_('COM_EASYSTAGING_PLAN_UPDATE_REMOTESITE_FAILED'));
				if(!$tableSettingsResult) $this->setError(JText::_('COM_EASYSTAGING_PLAN_UPDATE_TABLE_SETTINGS_FAILED'));
			}
		}
		return false;
	}
	
	/**
	 * Saves site records.
	 * @param int   $pk   - id of the plan
	 * @param array $data - data from one of the site tabs
	 * @param int   $type - local or remote site
	 * @return boolean    - true on success, false on failure.
	 */
	private function _saveSiteData ($pk, $data, $type = 1)
	{
		$table = JTable::getInstance('Site','EasyStagingTable');
		$isNew = true;
		
		// Add our Plan ID & Site type to the $data
		$data['plan_id'] = $pk;
		$data['type']    = $type;
		
		// Allow an exception to be thrown.
		try
		{
			// Load the row if saving an existing record.
			if ($table->load(array('plan_id' => $pk, 'type' => $type))) {
					
				$isNew = false;
			}
		
			// Bind the data.
			if (!$table->bind($data)) {
				$this->setError($table->getError());
				return false;
			}
		
			// Prepare the row for saving
			$this->prepareTable($table);
		
			// Check the data.
			if (!$table->check()) {
				$this->setError($table->getError());
				return false;
			}
		
			// Store the data.
			if (!$table->store()) {
				$this->setError($table->getError());
				return false;
			}
		}
		catch (Exception $e)
		{
			$this->setError($e->getMessage());
		
			return false;
		}
		
		$pkName = $table->getKeyName();
		
		switch ($type) {
			case 1:
			$name = 'localSite';
			break;
			
			case 2:
			$name = 'remoteSite';
			break;
			
			default:
				$name = '';
		}
		if (isset($table->$pkName) && !($name == '')) {
			$this->setState($name.'.id', $table->$pkName);
			$this->setState($name.'.new', $isNew);
		}
		
		return true;
	}
	
	private function _saveTableSettings($pk, $tableSettings)
	{
		$table = JTable::getInstance('Tables','EasyStagingTable');
		$isNew = true;
		
		foreach ($tableSettings as $tableName => $tableRow) {
			try {
				$tableRow['tablename'] = $tableName;
				$tableRow['plan_id'] = $pk;
				
				$table->load($tableRow['id']);
					
				// Bind the row.
				if (!$table->bind($tableRow)) {
					$this->setError($table->getError());
					return false;
				}
				
				// Store the data
				if(!$table->store()) {
					$this->setError($table->getError());
					return false;
				}
			}
			catch (Exception $e)
			{
				$this->setError($e->getMessage());
			
				return false;
			}
		}
		
		return true;
	}

	/*
	 * Returns basic site details from local configuration as JObject
	 * 
	 * @return JObject
	 */
	private function _getDefaultValuesFromLocal()
	{
		// Can we read in the configuration.php values as a starting point?
		$thisSiteConfig = JFactory::getConfig();
		$thisSite = new JObject();

		if ($thisSiteConfig) {
			$thisSite->site_name = $thisSiteConfig->get('sitename');
			$thisSite->database_name = $thisSiteConfig->get('db');
			$thisSite->database_user = $thisSiteConfig->get('user');
			$thisSite->database_password = $thisSiteConfig->get('password');
			$thisSite->database_host = $thisSiteConfig->get('host');
			$thisSite->database_table_prefix = $thisSiteConfig->get('dbprefix');
		}

		return $thisSite;
	}

	/*
	 * Returns the tables previously recorded for this site
	 * in an array suitable for the 'tables' table.
	 * 
	 * @param $plan_id - used to key the tables table against
	 * 
	 * @return array
	 */
	private function _getExistingTables($plan_id=0)
	{
		$tablesAlreadyAttachedToPlan= array();
		
		// Get the db
		$db = $this->getDbo();
		// Set the query
		$query = $db->getQuery(true);
		$query->select('*');
		$query->from('#__easystaging_tables');
		$query->where('plan_id = \''.$plan_id.'\'');
		$query->order('tablename');
		//echo $query;
		$db->setQuery($query);
		
		$siteTables = $db->loadAssocList();
		
		if(is_array($siteTables) && count($siteTables)) {
			// Run through the table list and add acceptable tables to our array to return. (This is in prep for table filtering.)
			foreach ($siteTables as $theTable) {
				$tablesAlreadyAttachedToPlan[$theTable['tablename']] = $theTable;
			}
			// Compare existing local tables to currently available tables.
			return $this->_updateTables($tablesAlreadyAttachedToPlan);
		} else {
			if(count($this->_forms) && !($plan_id == 0)) // In the odd event that a plan exists but has no table records we'll let the user know.
			{
				$msg = JText::sprintf('COM_EASYSTAGING_NO_TABLES_FOUND_FOR_PLAN', $plan_id);
				JFactory::getApplication()->enqueueMessage( $msg );
				$this->setState('easystaging.clean', 0);
			}
			// Then we'll send a copy of the current local tables to attach to the plan.
			return $this->_getLocalTables($plan_id,1);
		}
		return false;
	}
	
	/*
	 * Returns the current tables in the local database in an array suitable
	 * for the 'tables' table.
	 * 
	 * @param $plan_id - for future use
	 * @param $action  - action to set table to, defaults to 'Copy to Live'
	 * 
	 * @return array
	 */
	private function _getLocalTables($plan_id=0, $action=1)
	{
		// Create a db object.
		$db = JFactory::getDBO();
		$tableList = $db->getTableList();
		$localTables = array();
		
		// Run through the table list and add acceptable tables to our array to return.
		foreach ($tableList as $theTable) {
			if(!(strpos($theTable, '_easystaging') || strpos($theTable, '_session'))) { // we exclude our own & the session tables - no need to pollute the live site
				$localTables[$theTable] = array('id' => 0, 'plan_id' => $plan_id,'tablename' => $theTable, 'action' => $action, 'last' => '0000-00-00 00:00:00', 'lastresult' => 0);
			}
		}
		return $localTables;
	}
	
	/*
	 * Compares existing tables vs actual current tables and returns a updated array.
	 * We add/delete as needed taking care to set new tables to "Don't Copy".
	 * 
	 * @param $existingTables - the tables previously recorded for the plan
	 * 
	 * @return array
	 */
	private function _updateTables($existingTables)
	{
		// Get the local tables with a default action of "Don't Copy"
		if(count($existingTables))
		{
			$localTables = $this->_getLocalTables(0,0);
		} else {
			$localTables = $this->_getLocalTables();
		}
		
		
		// Find any tables that no longer exist on the local server
		$removedTables = array_diff_assoc($existingTables, $localTables);
		if(count($removedTables)) {
			// Trim the tables no longer available from the array
			$existingTables = $this->_reduceArray($existingTables, $removedTables);
			// Take the same tables out of the db
			
			// Advise the user that tables have been removed.
			if(count($this->_forms) && $this->_removeTables($removedTables)) {
				$changedMsg = JText::plural('COM_EASYSTAGING_TABLES_REMOVED', count($removedTables));
				JFactory::getApplication()->enqueueMessage( $changedMsg );
				$this->setState('easystaging.clean', 0);
			}
		}
		
		// Find any new tables
		$newTables = array_diff_assoc($localTables, $existingTables);
		if(count($newTables)) {
			$existingTables = array_merge($existingTables, $newTables);
			if(count($this->_forms)) {
				$changedMsg =  JText::plural('COM_EASYSTAGING_NEW_TABLES_FOUND', count($newTables));
				JFactory::getApplication()->enqueueMessage( $changedMsg );
				$this->setState('easystaging.clean', 0);
			}
		}
		
		return $existingTables;
	}

	private function _removeTables($tables)
	{
		$table = JTable::getInstance('Tables','EasyStagingTable');
		foreach ($tables as $aTable) {
			if(!$table->delete($aTable['id']))
			{
				JFactory::getApplication()->enqueueMessage( JText::sprintf('COM_EASYSTAGING_TABLE_NOT_REMOVED', $table['plan_id'], $table['tablename']) );
				$this->setState('easystaging.clean', 0);
				return false;
			}
		}

		return true;
	}
	/**
	 * Strips elements from the first array and returns it
	 * 
	 * @return array
	 */
	private function _reduceArray($origArray, $itemsToDelete)
	{
// Enter
		foreach ($itemsToDelete as $key => $value) {
			if(key_exists($key, $origArray)) unset($origArray[$key]);
		}
		return $origArray;
	}
}
