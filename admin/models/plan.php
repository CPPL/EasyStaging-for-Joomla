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
		$form = $this->loadForm('com_easystaging.plan', 'plan',
		                        array('control' => 'jform', 'load_data' => $loadData));
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
		if ($item = parent::getItem($pk)) {
			$plan_id = $item->id;
			// Add the related data here?
			// Get the sites for this plan
			$Sites = JTable::getInstance('Site', 'EasyStagingTable');
			// Get the local site settings
			$localSite = $Sites->load(array('plan_id'=>$plan_id, 'type'=>'1'));
			if($localSite) {
				$localSite = $Sites->getProperties();
				// Get the Tables for local site.
				$item->localTables = $this->_getLocalTables($localSite['id']);
			} else {
				// No local site! 
				// Get the Tables currently running site.
				$item->localTables = $this->_getLocalTables(0);
				$localSite = $this->_getDefaultValuesFromLocal()->getProperties();
				$localSite['site_url'] = JURI::root();
				$localSite['site_path'] = JPATH_BASE;
				$localSite['rsync_options'] = '-avr';
				
			}
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
			}
			$item->remoteSite = $remoteSite;

		}

		return $item;
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
	 * Returns the current tables in the local database in an array suitable
	 * for the 'tables' table.
	 * 
	 * @return JObject
	 */
	private function _getLocalTables($site_id)
	{
		// Create a new query object.
		$db = JFactory::getDBO();
		$tableList = $db->getTableList();
		$localTables = array();
		
		// Run through the table list and add acceptable tables to our array to return.
		foreach ($tableList as $theTable) {
			$localTables[] = array('id' => 0, 'site_id' => $site_id,'tablename' => $theTable, 'action' => 1, 'last' => '0000-00-00 00:00:00', 'lastresult' => 0);
		}
		/*
		* @todo	Compare local tables to ones on record and add/delete as needed
		* 			taking care to set new tables to "Don't Copy".
		* 			Probably need to alert user to changed tables as well...
		*/
		
		return $localTables;
	}
}
