<?php
/* @package		EasyStaging
 * @link		http://seepeoplesoftware.com
 * @license		GNU/GPL
 * @copyright	Craig Phillips Pty Ltd
*/
 
// No direct access
 
defined( '_JEXEC' ) or die( 'Restricted access' );
 
jimport('joomla.application.component.controller');
jimport( 'joomla.database.table' );

/**
 * EasyStaging Component Plan Controller
 */
class EasyStagingControllerPlan extends JController
{
	protected $plan_id;

	function hello()
	{
		// Check for request forgeries
		if ($this->_tokenOK() && ($plan_id = $this->_plan_id())) {
			echo json_encode(array('msg' => JText::_( 'COM_EASYSTAGING__EASYSTAGING_IS_READY' ) , 'status' => 1));
		} else {
			echo json_encode(array('msg' => JText::_( 'COM_EASYSTAGING_PLAN_ID_TOKE_DESC' ) , 'status' => 0));
		}
	}
	
	function setupRsync()
	{
		// Check for request forgeries
		if ($this->_tokenOK() && ($plan_id = $this->_plan_id())) {
			// $plan_id = JRequest::get('plan_id');
			$rsResult = $this->_createRSYNCExclusionFile($plan_id);
			if($rsResult['status'])
			{
				$fileCreated = $rsResult['fileName'];
				echo json_encode(array('msg' => JText::sprintf('COM_EASYSTAGING_JSON_RSYNC_STEP_SUCCEEDED', $fileCreated), 'status' => 1, 'data' => $rsResult));
			} else {
				echo json_encode(array('msg' => JText::sprintf('COM_EASYSTAGING_JSON_RSYNC_STEP_FAILED', $plan_id), 'status' => 0,  'data' => $rsResult));
			}
		}
	}

	function runRsync()
	{
		// Check for request forgeries
		if ($this->_tokenOK() && ($plan_id = $this->_plan_id())) {
			// first we add the rsync options
			$rsyncCmd = 'rsync '.$this->_getRsyncOptions($plan_id);
			// then we add the exclusions file name
			$rsyncCmd.= ' --exclude-from='.$this->_getInputVar('fileName');
			
			// add the source
			$rsyncCmd.= ' '.$this->_loadLocalSiteRecord($plan_id)->site_path; 
			// add the destination
			$rsyncCmd.= ' '.$this->_loadRemoteSiteRecord($plan_id)->site_path;

			// exec the rsync command
			$rsyncOutput = array();
			exec($rsyncCmd, $rsyncOutput);

			//$rsyncOutput = exec('uptime');  
			$rsyncOutput[] = '<br />'.$rsyncCmd;
			
			echo json_encode(array('msg' => JText::sprintf('COM_EASYSTAGING_RSYNC_STEP_2_DESC',$plan_id), 'status' => 1, 'data' => $rsyncOutput));
		}
	}

	/**
	 * Check the connection to the remote database ...
	 */
	function checkDBConnection()
	{
		// Check for request forgeries
		if ($this->_tokenOK() && ($plan_id = $this->_plan_id())) {
			// Get the remote site details
			$rs = $this->_loadRemoteSiteRecord($plan_id);
			$options	= array ('host' => $rs->database_host, 'user' => $rs->database_user, 'password' => $rs->database_password, 'database' => $rs->database_name, 'prefix' => $rs->database_table_prefix);
			$rDBC = JDatabase::getInstance($options);
			
			if($rDBC) {
				$msg = JText::_( 'COM_EASYSTAGING_DATABASE_STEP_01_CONNECTED' );
				$remoteTablesRetreived = $this->_getRemoteDBTables($rDBC);
				echo json_encode(array('msg' => $msg, 'status' => 1, 'data' => $remoteTablesRetreived));
			} else {
				echo json_encode(array('msg' => JText::_( 'COM_EASYSTAGING_DATABASE_STEP_01_FAILED_TO_CONNECT' ) , 'status' => 0, 'data' => $rDBC->errors));
			}
		}
	}

	/**
	 * Build and return a json data block with the tables to be copied...
	 */
	function getDBTables()
	{
		// Check for request forgeries
		if ($this->_tokenOK() && ($plan_id = $this->_plan_id())) {
			// Get list of tables we'll be acting on
			$remoteTableList = $this->_getInputVar('remoteTableList');
			$tableResults = $this->_getTablesForReplication($plan_id, $remoteTableList);
			if($tableResults) {
				$response = array('msg' => JText::_('COM_EASYSTAGING_DATABASE_STEP_02_TABLES_LIST'), 'status' => 1, 'data' => $tableResults, 'tablesFound' => count($tableResults['rows']) );
				$initialTableResults = $this->_getTablesForInitialReplication($plan_id);
				if($initialTableResults['status'] != '0') {
					$response['msg'] = $response['msg'].'<br />'.JText::sprintf('COM_EASYSTAGING_FOUND_TABLES_FO_DESC',count($initialTableResults['rows']));
					$response['initialCopyTables'] = $initialTableResults['rows'];
				} else {
					$response['msg'] = $response['msg'].'<br />'.JText::_('COM_EASYSTAGING_NO_TABLES_FO_DESC');
				}

			} else {
				$response = array('msg' => JText::_( 'COM_EASYSTAGING_DATABASE_STEP_02_FAILED' ) , 'status' => 0, 'data' => $tableResults);
			}
		} else {
			$response = array('msg' => JText::_( 'COM_EASYSTAGING_PLAN_ID_TOKE_DESC' ) , 'status' => 0);
		}

		echo json_encode($response);
	}

	/**
	 * Build an SQL export file for a named table...
	 */
	function createTableExportFile()
	{
		// Setup base variables
		$buildTableSQL = '';
		$log    = '';
		$data 	= '';
		$status = 0;

		// Check for request forgeries
		if ($this->_tokenOK() && ($plan_id = $this->_plan_id())) {
			$log = JText::_('COM_EASYSTAGING_TOKEN_PLAN_VALID');
			
			$jinput =  JFactory::getApplication()->input;
			$table = $jinput->get('tableName', '');
			
			if($table != '') {
				// OK were, going to need access to the database
				$db = JFactory::getDbo();
				$dbTableName = $db->nameQuote($table);
				
				// Build our SQL to recreate the table on the remote server.
				// 1. First we drop the existing table
				$buildTableSQL.= 'DROP TABLE IF EXISTS '.$dbTableName.';';
				
				// 2. Then we create it again, except with a new prefix :D
				$db->setQuery('SHOW CREATE TABLE '.$dbTableName);
				$createStatement = $db->loadRow();
				$buildTableSQL.= "\n\n".$createStatement[1].";\n\n";
				// Ok a bit of search and replace to upate the prefix.
				$buildTableSQL = $this->_changeTablePrefix($buildTableSQL);
				
				// 3. Next we try and get the records in the table (after all no point in creating an insert statement if there are no records :D
				$q = 'SELECT * FROM '.$dbTableName;
				$db->setQuery($q);
				if(($records = $db->loadRowList()) != null)
				{
					$log.= '<br />'.JText::sprintf('COM_EASYSTAGING_CREATING_INSERT_STATEMEN_DESC',count($records));
					// 4. Then we build the list of field/column names that we'll insert data into
					// -- first we get the columns
					$tables = $db->getTableFields($table);
					$flds = $this->_getArrayOfFieldNames($tables);
					$num_fields = count($flds);
					
					// -- then we implode them into a suitable statement
					$columnInsertSQL = 'INSERT INTO '.$this->_changeTablePrefix($dbTableName).' ('.implode( ', ' , $flds ).') VALUES ';
					
					$buildTableSQL.= $columnInsertSQL;
					
					// 5. Now we can process the rows into INSERT values
					$valuesSQL = array();
					foreach ($records as $row) {
						foreach ($row as $field => $value) {
							$row[$field] = addslashes($value);
							$row[$field] = str_replace("\n","\\n",$row[$field]);
						}
						$valuesSQL[] = "('". implode('\', \'', $row) ."')"; 
					}
					$valuesSQL = implode(', ', $valuesSQL);

					$buildTableSQL .= "\n\n".$valuesSQL."\n\n";					
				} else {
					$log.= '<br />'.JText::sprintf('COM_EASYSTAGING_JSON__S_IS_EMPTY_NO_INS_REQ', $table) ;
				}

				// 6. Save the export SQL to file for the next request to execute.
				// Build our file path & file handle
				$pathToSQLFile = $this->_sync_files_path().$this->_export_file_name($table);
				$data = $pathToSQLFile;
				if($exportSQLFile = @fopen($pathToSQLFile, 'w')) {
					// Attempt to write the file
					$status = fwrite($exportSQLFile, $buildTableSQL);
					// Time to close off
					fclose($exportSQLFile);
					$response = array('msg' => JText::sprintf('COM_EASYSTAGING_SQL_EXPORT_SUCC', $table), 'status' => $status, 'data' => $data, 'log' => $log);
				} else {
					$response = array('msg' => JText::_('COM_EASYSTAGING_JSON_FAILED_TO_OPEN_SQL_EXP_FILE'), 'status' => $exportSQLFile, 'data' => error_get_last(), 'log' => $log);
				}

			} else {
				// If we got here things didn't go well ;)
				$response = array('msg' => JText::_('COM_EASYSTAGING_TABLE_COPY_FAILED'), 'status' => 0,  'data' => $return, 'log' => $log);
			}
		} else {
			$response = array('msg' => JText::_( 'COM_EASYSTAGING_PLAN_ID_TOKE_DESC' ) , 'status' => 0, 'data' => array());
		}
		echo json_encode($response);
	}

	function finishRun()
	{
		// Check for request forgeries
		if ($this->_tokenOK() && ($plan_id = $this->_plan_id())) {
			// Load our plan record
			JTable::addIncludePath(JPATH_ADMINISTRATOR.'/components/com_easystaging/tables');
			$Plan = JTable::getInstance('Plan', 'EasyStagingTable');
			
			if($Plan->load(array('id'=>$plan_id)))
			{
				// Initialise variables.
				$date = JFactory::getDate();
				$Plan->last_run = $date->toMySQL();
				$Plan->store();
				$format = JText::_('DATE_FORMAT_LC2');
				$msg = JText::sprintf('COM_EASYSTAGING_LAST_RUN',$date->format($format,true));
				$result = array( 'msg' => $msg );
				echo json_encode( $result );
			}
			return;
		}
		
		return false;
	}

	private function _getRemoteDBTables($db)
	{
		$tableList = $db->getTableList();

		return $tableList;
	}

	/**
	 * Strips out just the field names from the assoc array provided by Joomla!
	 * @param array $tables
	 * @return single list of field names 
	 */
	private function _getArrayOfFieldNames($tables)
	{
		
		$db = JFactory::getDbo();
		$fieldNames = array();
		foreach ($tables as $tableName => $tableFields) {
			foreach ($tableFields as $aField => $aFieldType) {
				$fieldNames[] = $db->nameQuote($aField);
			}
		}
		return $fieldNames;
	}

	private function _changeTablePrefix($buildTableSQL)
	{
		$localSite = $this->_loadLocalSiteRecord($this->_plan_id());
		$localPrefix = $localSite->database_table_prefix;
		$remoteSite = $this->_loadRemoteSiteRecord($this->_plan_id());
		$remotePrefix = $remoteSite->database_table_prefix;
		return str_replace($localPrefix, $remotePrefix, $buildTableSQL);
	}
	
	private function _getTablesForReplication($plan_id, $remoteTableList)
	{
		if(isset($plan_id))
		{
			$db = JFactory::getDbo();
			$db->setQuery("select * from `#__easystaging_tables` where `plan_id` = ".$plan_id." and (`action` = '1' or `action` = '2')");
			if($localTableRows = $db->loadAssocList()) {
				$tableRows = array(); // Where we'll store the tables to be copied.
				// Loop through the table settings and adding them to the $tableRows to be used if their action suites.
				foreach ($localTableRows as $localTable) {
					// If this table is set to 'Copy To Live' we add it to our array
					if($localTable['action'] == 1){
						$tableRows[] = $localTable;
					} else { // It's a copy if not exists table
						if(!in_array($localTable['tablename'], $remoteTableList)) {
							$tableRows[] = $localTable;
						}
					}
				}

				$tableResults = array();
				$tableResults['msg'] = JText::sprintf('COM_EASYSTAGING_TABLES_SUCCESSFULLY_RETREIVE_FOR_PLAN', $plan_id, count($tableRows));
				$tableResults['rows'] = $tableRows;
				$tableResults['status'] = count($tableRows);
				return $tableResults;
			} else {
				return array("msg" => JText::sprintf('COM_EASYSTAGING_FAILED_TO_RETRIEV_TABLES_FROM_DB', $plan_id), 'status' => 0);
			}
		}

		return array("msg" => JText::_('COM_EASYSTAGING_NO_PLAN_ID_AVAIL'), 'status' => 0);
	}

	private function _getTablesForInitialReplication($plan_id)
	{
		if(isset($plan_id))
		{
			$db = JFactory::getDbo();
			$db->setQuery("select * from `#__easystaging_tables` where `plan_id` = ".$plan_id." and `action` = '2'");
			if($tableRows = $db->loadAssocList()) {
				$tableResults = array();
				$tableResults['msg'] = JText::sprintf('COM_EASYSTAGING_INITIAL_REPLICATION_TABLE_DESC', $plan_id, count($tableRows));
				$tableResults['rows'] = $tableRows;
				$tableResults['status'] = count($tableRows);
				return $tableResults;
			} else {
				return array("msg" => JText::sprintf('COM_EASYSTAGING_FAILED_TO_RETRIEV_DESC', $plan_id), 'status' => 0);
			}
		}

		return array("msg" => JText::_('COM_EASYSTAGING_NO_PLAN_ID_AVAIL'), 'status' => 0);
	}

	private function _loadLocalSiteRecord($plan_id)
	{
		$type = 1; // Local site
		return $this->_loadSiteRecord($plan_id, $type);
	}
	private function _loadRemoteSiteRecord($plan_id)
	{
		$type = 2; // Live/Target site
		return $this->_loadSiteRecord($plan_id, $type);
	}
	private function _loadSiteRecord($plan_id, $type)
	{
		// Load our site record
		JTable::addIncludePath(JPATH_ADMINISTRATOR.'/components/com_easystaging/tables');
		$Sites = JTable::getInstance('Site', 'EasyStagingTable');
		
		if($Sites->load(array('plan_id'=>$plan_id, 'type'=>$type)))
		{
			return $Sites;
		}
	}

	private function _createRSYNCExclusionFile($plan_id)
	{
		if(isset($plan_id))
		{
			// Build our file path & file handle
			$pathToExclusionsFile = $this->_sync_files_path().$this->_excl_file_name();
			$result = array('fileName' => $this->_excl_file_name());
			$result['fullPathToExclusionFile'] = $pathToExclusionsFile;
			
			if($exclusionFile = @fopen($pathToExclusionsFile, 'w')){
				
				// Create the content for our exclusions file
				$defaultExclusions = <<< EOH
-tmp/
-logs/
-cache/
-configuration.php

EOH;
				// Get local site record
				$Sites = $this->_loadLocalSiteRecord($plan_id);
				// Combine the default exclusions with those in the local site record
				$allExclusions = $defaultExclusions.$this->_checkExclusionField($Sites->file_exclusions);
				$result['fileData'] = $allExclusions;
				
				// Attempt to write the file
				$result['status'] = fwrite($exclusionFile, $allExclusions);
				$result['msg'] = $result['status'] ? JText::sprintf('COM_EASYSTAGING_FILE_WRITTEN_SUCCESSFULL_DESC',$result['status']) : JText::_('COM_EASYSTAGING_FAILED_TO_WRIT_DESC') ;
				
				// Time to close off
				fclose($exclusionFile);
			} else {
				$result['status'] = 0;
				$result['msg'] = JText::_('COM_EASYSTAGING_JSON_UNABLE_TO_OPEN_RSYNC_EXC_FILE');
			}
			// Return to Maine, where the moose, deer, eagles and loons roam.
			return $result;
		}
		return false;
	}

	private function _sync_files_path()
	{
		return JPATH_ADMINISTRATOR.'/components/com_easystaging/syncfiles/';
	}
	private function _excl_file_name()
	{
		return ('plan-'.$this->_plan_id().'-exclusions.txt');
	}
	private function _export_file_name($table)
	{
		return ('plan-'.$this->_plan_id().'-'.$table.'-export.sql');
	}

	private function _plan_id()
	{
		if(isset($plan_id)) {
			return $plan_id;
		} else {
			$plan_id = $this->_getInputVar('plan_id', 0, 'INT');
			return $plan_id;
		}
	}

	private function _getInputVar($varName, $defaultValue = '', $type = NULL)
	{
		$jinput =  JFactory::getApplication()->input;
		$varValue = $jinput->get($varName, $defaultValue, $type);
		return $varValue;
	}

	/**
	 * Checks $file_exclusions to ensure each line starts with a "-" as required by rsync ...
	 * @param string $file_exclusions
	 * @return string|boolean - false on failure
	 */
	private function _checkExclusionField($file_exclusions)
	{
		if(isset($file_exclusions) && ($file_exclusions != ''))
		{
			$result = array();
			$file_exclusions = explode("\n", str_replace("\r\n", "\n", $file_exclusions)); // Just in case, we convert all \n\r before exploding
			foreach ($file_exclusions as $fe_line) {
				$fe_line = trim($fe_line);
				if($fe_line[0] != "-") $fe_line = '-'.$fe_line;
				$result[] = $fe_line;
			}
			return implode("\n", $result);
		} else {
			return false;
		}
	}

	private function _getRsyncOptions($plan_id)
	{
		//place holder, will get from plan record
		$SiteRecord = $this->_loadLocalSiteRecord($plan_id);
		$opts = $SiteRecord->rsync_options;
		return $opts;
	}
	
	private function _tokenOK()
	{
		// Check for request forgeries
		if (!JRequest::checkToken('request')) {
			$response = array(
						'status' => '0',
						'msg' => JText::_('JINVALID_TOKEN')
			);
			echo json_encode($response);
			return false;
		}

		return true;
	}
}
