<?php
/**
 * EasyStaging Model for EasyStaging Component
 * @link        http://seepeoplesoftware.com
 * @license     GNU/GPL
 * @copyright   Craig Phillips Pty Ltd
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
     * Method override to check if you can edit an existing record.
     *
     * @param    array   $data   An array of input data.
     * @param    string  $key    The name of the key for the primary key.
     *
     * @return null
     */
    protected function allowEdit($data = array(), $key = 'id')
    {
        // Check specific edit permission then general edit permission.
        return JFactory::getUser()->authorise('core.edit', 'com_easystaging.plan.'.
        ((int) isset($data[$key]) ? $data[$key] : 0))
        or parent::allowEdit($data, $key);
    }

    /**
     * Returns a reference to the a Table object, always creating it.
     *
     * @param   type    The table type to instantiate
     * @param   string  A prefix for the table class name. Optional.
     * @param   array   Configuration array for model. Optional.
     * @return  JTable  A database object
     * @since   1.6
     */
    public function getTable($type = 'Plan', $prefix = 'EasyStagingTable', $config = array())
    {
        return JTable::getInstance($type, $prefix, $config);
    }
    /**
     * Method to get the record form.
     *
     * @param   array   $data       Data for the form.
     * @param   boolean $loadData   True if the form is to load its own data (default case), false if not.
     * @return  mixed   A JForm object on success, false on failure
     * @since   1.6
     */
    public function getForm($data = array(), $loadData = true)
    {
        // Get the form.
        $form = $this->loadForm('com_easystaging.plan', 'plan', array('control' => 'jform', 'load_data' => $loadData));
        if (empty($form)) {
            return false;
        }
        return $form;
    }

    /**
     * Method to get the data that should be injected in the form.
     *
     * @return  mixed   The data for the form.
     * @since   1.6
     */
    protected function loadFormData()
    {
        // Check the session for previously entered form data.
        $data = JFactory::getApplication()->getUserState('com_easystaging.edit.plan.data', array());
        if (empty($data)) {
            $data = $this->getItem();
        }
        return $data;
    }

    /**
     * Method to get a single record.
     *
     * @param   integer The id of the primary key.
     *
     * @return  mixed   Object on success, false on failure.
     */
    public function getItem($pk = null)
    {
        $this->setState('easystaging.clean', 1);

        if ($item = parent::getItem($pk)) {
            $item->clean = true;
            $plan_id = intval($item->id);
            
            // Get the sites table
            $Sites = JTable::getInstance('Site', 'EasyStagingTable');
            // Get the local site settings
            $localSite = $Sites->load(array('plan_id'=>$plan_id, 'type'=>'1'));
            if ($localSite) {
                $localSite = $Sites->getProperties(); // We need it in an array
            } else {
                // No local site!
                $localSite = $this->_getDefaultValuesFromLocal()->getProperties();
                $localSite['site_url'] = JURI::root();
                $localSite['site_path'] = JPATH_SITE;
                $localSite['rsync_options'] = '-avr --delete';
                // In the odd event that a plan exists but has NO local site data we'll let the user know.
                // As getItem() gets called twice (first in getForm) we only need to tell the user once
                if (count($this->_forms) && !($plan_id == 0)) {
                    JFactory::getApplication()->enqueueMessage(
                        JText::sprintf('COM_EASYSTAGING_NO_LOCAL_SITE_FOUND_FOR_PLAN', $plan_id)
                    );
                }
            }

            // Get the Tables for local site.
            $localTables = $this->_getExistingTables($plan_id);

            $item->localTables = $localTables;
            $item->localSite = $localSite;

            // Get the remote site settings
            $remoteSite = $Sites->load(array('plan_id'=>$plan_id, 'type'=>'2'));
            if ($remoteSite) {
                $remoteSite = $Sites->getProperties();
            } else {
                // No remote site! Get some defaults
                $remoteSite = $this->_getDefaultValuesFromLocal()->getProperties();
                $remoteSite['site_url'] = 'http://';
                $remoteSite['site_path'] = 'public_html/';
                $remoteSite['database_host'] = 'name.ofLiveServer.com';
                // In the odd event that a plan exists but has NO remote site data we'll let the user know.
                if (count($this->_forms) && !($plan_id == 0)) {
                    $item->clean = false;
                    $this->setState('easystaging.clean', 0);
                    JFactory::getApplication()->enqueueMessage(
                        JText::sprintf('COM_EASYSTAGING_NO_REMOTE_SITE_FOUND_FOR_PLAN', $plan_id),
                        'WARNING'
                    );
                }
            }

            $item->remoteSite = $remoteSite;

            // Get the fileCopyActions (Rsyncs)
            $fileCopyActions= $this->_getFileCopyActions($plan_id);
            $item->fileCopyActions = $fileCopyActions;
        }

        if (count($this->_forms) && !$this->getState('easystaging.clean', 1)) {
            $mq = JFactory::getApplication()->getMessageQueue();
            if (count($mq)) {
                $item->clean = false;
                JFactory::getApplication()->enqueueMessage(
                    JText::sprintf('COM_EASYSTAGING_SAVE_PLAN_BEFORE_USING', $plan_id),
                    'WARNING'
                );
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
        $data['fileCopyActions'] = JRequest::getVar('fileCopyActions', array(), 'post', 'array');
        
        if (parent::save($data)) {
            // Check to see if it's a new Plan
            if ($this->getState('plan.new', 1)) {   // Update the 'id' in $data to the newly saved Plan's id.
                $data['id'] = $this->getState('plan.id', 0);
            }
            
            // Store the localSite record
            $localSiteResult = $this->_saveSiteData($data['id'], $data['localSite'], 1);
            
            // Store the remoteSite record
            $remoteSiteResult = $this->_saveSiteData($data['id'], $data['remoteSite'], 2);
            
            // Store the table settings
            $tableSettingsResult = $this->_saveTableSettings($data['id'], $data['tableSettings']);

            // Store the file copy actions
            $fileCopyActionsResult = $this->_saveFileCopyActions($data['id'], $data['fileCopyActions']);
            
            if ($localSiteResult && $remoteSiteResult && $tableSettingsResult) {
                return true;
            } else {
                if (!$localSiteResult) {
                    $this->setError(JText::_('COM_EASYSTAGING_PLAN_UPDATE_LOCALSITE_FAILED'));
                }
                if (!$remoteSiteResult) {
                    $this->setError(JText::_('COM_EASYSTAGING_PLAN_UPDATE_REMOTESITE_FAILED'));
                }
                if (!$tableSettingsResult) {
                    $this->setError(JText::_('COM_EASYSTAGING_PLAN_UPDATE_TABLE_SETTINGS_FAILED'));
                }
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
        $table = JTable::getInstance('Site', 'EasyStagingTable');
        $isNew = true;
        
        // Add our Plan ID & Site type to the $data
        $data['plan_id'] = $pk;
        $data['type']    = $type;
        
        // Allow an exception to be thrown.
        try {
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
        } catch (Exception $e) {
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
        $table = JTable::getInstance('Tables', 'EasyStagingTable');

        foreach ($tableSettings as $tableName => $tableRow) {
            try {
                $tableRow['tablename'] = $tableName;
                $tableRow['plan_id'] = $pk;
                
                $table->load($tableRow['id']);
                    
                // Save the row.
                if (!$table->save($tableRow)) {
                    $this->setError($table->getError());
                    return false;
                }
            } catch (Exception $e) {
                $this->setError($e->getMessage());
            
                return false;
            }
        }
        
        return true;
    }

    private function _saveFileCopyActions($pk, $fileCopyActions)
    {
        $table = JTable::getInstance('Rsyncs', 'EasyStagingTable');

        foreach ($fileCopyActions as $tableRow) {
            try {
                $tableRow['plan_id'] = $pk;
                $id = $tableRow['id'];

                // If it's an existing action
                if ($id) {
                    $table->load($id);

                    if (($tableRow['label'] == '') || ($tableRow['source_path'] == '') || ($tableRow['target_path'] == ''))
                    {
                        // We delete rows that have missing elements
                        $table->delete($id);
                        continue;
                    }
                } else {
                    $tableRow['id'] = '';
                    if (($tableRow['label'] == '') || ($tableRow['source_path'] == '') || ($tableRow['target_path'] == '')) {
                        // No label we don't save it.
                        continue;
                    }
                }

                // Save the row.
                if (!$table->save($tableRow)) {
                    $this->setError($table->getError());
                    return false;
                }
            } catch (Exception $e) {
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
            $thisSite->database_name = '';
            $thisSite->database_user = '';
            $thisSite->database_password = '';
            $thisSite->database_host = '';
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
        $query->from($db->quoteName('#__easystaging_tables'));
        $query->where($db->quoteName('plan_id') . ' = ' . $db->quote($plan_id));
        $query->order('tablename');
        //echo $query;
        $db->setQuery($query);
        
        $siteTables = $db->loadAssocList();
        
        if (is_array($siteTables) && count($siteTables)) {
            // Run through the table list and add acceptable tables to our array to return. (This is in prep for table filtering.)
            foreach ($siteTables as $theTable) {
                $tablesAlreadyAttachedToPlan[$theTable['tablename']] = $theTable;
            }
            // Compare existing local tables to currently available tables.
            return $this->_updateTables($tablesAlreadyAttachedToPlan);
        } else {
            if (count($this->_forms) && !($plan_id == 0)) {
                // In the odd event that a plan exists but has no table records we'll let the user know.
                $msg = JText::sprintf('COM_EASYSTAGING_NO_TABLES_FOUND_FOR_PLAN', $plan_id);
                JFactory::getApplication()->enqueueMessage($msg, 'WARNING');
                $this->setState('easystaging.clean', 0);
            }
            // Then we'll send a copy of the current local tables to attach to the plan.
            // Get the local tables with the default action
            /* @var $esParams JRegistry */
            $esParams = JComponentHelper::getParams('com_easystaging');
            $defaultAction = $esParams->get('default_table_action', 0);

            return $this->_getLocalTables($plan_id, $defaultAction);
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
            // we exclude our own tables - no need to pollute the live site
            if (!(strpos($theTable, '_easystaging'))) {
                // By default we set the _session table to copy only if it doesn't exist
                if (strpos($theTable, '_session') && ($action == 1)) {
                    $actionToUse = 2;
                } else {
                    $actionToUse = $action;
                }
                $localTables[$theTable] = array(
                    'id'        => 0,
                    'plan_id'   => $plan_id,
                    'tablename' => $theTable,
                    'action'    => $actionToUse,
                    'last' => '0000-00-00 00:00:00',
                    'lastresult' => 0,
                );
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
        // Get the local tables with the default action
        /* @var $esParams JRegistry */
        $esParams = JComponentHelper::getParams('com_easystaging');
        $defaultAction = $esParams->get('default_added_table_action', 0);

        if (count($existingTables)) {
            $localTables = $this->_getLocalTables(0, $defaultAction);
        } else {
            $localTables = $this->_getLocalTables();
        }
        
        
        // Find any tables that no longer exist on the local server
        $removedTables = array_diff_key($existingTables, $localTables);
        if (count($removedTables)) {
            // Trim the tables no longer available from the array
            $existingTables = $this->_reduceArray($existingTables, $removedTables);
            // Take the same tables out of the db
            
            // Advise the user that tables have been removed.
            if (count($this->_forms) && $this->_removeTables($removedTables)) {
                $changedMsg = JText::plural('COM_EASYSTAGING_TABLES_REMOVED', count($removedTables));
                JFactory::getApplication()->enqueueMessage($changedMsg, 'WARNING');
                $this->setState('easystaging.clean', 0);
            }
        }
        
        // Find any new tables
        $newTables = array_diff_key($localTables, $existingTables);
        if (count($newTables)) {
            $existingTables = array_merge($existingTables, $newTables);
            if (count($this->_forms)) {
                $changedMsg =  JText::plural('COM_EASYSTAGING_NEW_TABLES_FOUND', count($newTables));
                JFactory::getApplication()->enqueueMessage($changedMsg, 'WARNING');
                $this->setState('easystaging.clean', 0);
            }
        }
        
        return $existingTables;
    }

    private function _removeTables($tables)
    {
        $table = JTable::getInstance('Tables', 'EasyStagingTable');
        foreach ($tables as $aTable) {
            if (!$table->delete($aTable['id'])) {
                JFactory::getApplication()->enqueueMessage(
                    JText::sprintf('COM_EASYSTAGING_TABLE_NOT_REMOVED', $table['plan_id'], $table['tablename'])
                );
                $this->setState('easystaging.clean', 0);
                return false;
            }
        }

        return true;
    }

    /**
     * Returns the tables previously recorded for this site
     * in an array suitable for the 'tables' table.
     *
     * @param $plan_id - used to key the tables table against
     *
     * @return array
     */
    private function _getFileCopyActions($plan_id=0)
    {
        // Our proto rsync action
        $newRsync = array(
            'id' => '0',
            'plan_id' => $plan_id,
            'direction' => '',
            'label' => '',
            'source_path' => '',
            'target_path' => '',
            'last' => '',
            'last_result' => '',
        );

        // Get the db
        $db = $this->getDbo();
        // Set the query
        $query = $db->getQuery(true);
        $query->select('*');
        $query->from($db->quoteName('#__easystaging_rsyncs'));
        $query->where($db->quoteName('plan_id') . ' = ' . $db->quote($plan_id));
        $query->order('id');
        $db->setQuery($query);

        $rsyncs = $db->loadAssocList();

        if (is_array($rsyncs) && count($rsyncs) == 0) {
            // An Empty array means no rsyncs yet
            $rsyncs = array($newRsync);
        } elseif (is_array($rsyncs) && count($rsyncs) > 0) {
            // Add a blank to the end
            $rsyncs[] = $newRsync;

            // Process our array for display
            foreach ($rsyncs as &$row) {
                $last = $row['last'];
                $lastResult = $row['last_result'];

                if ($last == '0000-00-00 00:00:00') {
                    $row['lastActionResult'] = JText::_('COM_EASYSTAGING_RSYNCS_NO_LAST_ACTION_RESULT');
                } elseif ($last == '') {
                    $row['last'] = JText::_('COM_EASYSTAGING_RSYNCS_NEW_RSYNC_ACTION');
                } else {
                    $row['last'] = strtotime($last);
                    $row['last_result'] = (
                    $lastResult ? JText::_('COM_EASYSTAGING_TABLE_LAST_RESULT_SUCCESS') :
                        JText::_('COM_EASYSTAGING_TABLE_LAST_RESULT_FAIL')
                    );
                    $row['lastActionResult'] = JText::sprintf(
                        'COM_EASYSTAGING_TABLE_LAST_ACTION_RESULT',
                        $row['last'],
                        $row['last_result']
                    );
                }
            }
        } else {
            $rsyncs = false;
        }

        return $rsyncs;
    }

    /**
     * Strips elements from the first array and returns it
     *
     * @param   array  $origArray      The original array
     *
     * @param   array  $itemsToDelete  The array of keys to remove from the original array
     *
     * @return array
     */
    private function _reduceArray($origArray, $itemsToDelete)
    {
        foreach ($itemsToDelete as $key => $value) {
            if (array_key_exists($key, $origArray)) {
                unset($origArray[$key]);
            }
        }
        return $origArray;
    }
}
