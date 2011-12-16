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
			echo json_encode(array('msg' => 'EasyStaging is ready.', 'status' => 1));
		} else {
			echo json_encode(array('msg' => 'Plan ID/token is missing.', 'status' => 0));
		}
	}
	
	function doRsyncStep01()
	{
		// Check for request forgeries
		if ($this->_tokenOK() && ($plan_id = $this->_plan_id())) {
			// $plan_id = JRequest::get('plan_id');
			if($rsResult = $this->_createRSYNCExclusionFile($plan_id))
			{
				$fileCreated = $rsResult['fileName'];
				echo json_encode(array('msg' => JText::sprintf('COM_EASYSTAGING_JSON_RSYNC_STEP_SUCCEEDED', $fileCreated), 'status' => 1, 'data' => $rsResult));
			} else {
				echo json_encode(array('msg' => JText::sprintf('COM_EASYSTAGING_JSON_RSYNC_STEP_FAILED', $plan_id), 'status' => 0));
			}
		}
	}

	function doRsyncStep02()
	{
		// Check for request forgeries
		if ($this->_tokenOK() && ($plan_id = $this->_plan_id())) {
			$rsyncCmd = $this->_getRsyncOptions($plan_id);
	
			$rsyncOutput = array();
			// exec($rsyncCmd, $rsyncOutput);

			$rsyncOutput = exec('uptime');
			
			echo json_encode(array('msg' => 'RSYNC Step #2 for Plan ID: '.$plan_id, 'status' => 1, 'data' => $rsyncOutput));
		}
	}

	function doDBaseStep01()
	{
		// Check for request forgeries
		if ($this->_tokenOK() && ($plan_id = $this->_plan_id())) {
			// Get list of tables we'll be acting on
			$tableResults = $this->_getTablesForReplication($plan_id);
			if($tableResults) {
				echo json_encode(array('msg' => 'Database Step 01 - Table list retreived successfully.', 'status' => 1, 'data' => $tableResults));
			} else {
				echo json_encode(array('msg' => 'Database Step 01 - FAILED', 'status' => 0, 'data' => $tableResults));
			}
		}
	}

	function doDBaseTableCopy()
	{
		// Setup base variables
		$buildTableSQL = '';
		$log    = '';

		// Check for request forgeries
		if ($this->_tokenOK() && ($plan_id = $this->_plan_id())) {
			$log = JText::_('Token & plan ID are valid.');
			
			$jinput =  JFactory::getApplication()->input;
			$table = $jinput->get('tableName', '');
			
			if($table != '') {
				$log .= JText::_('Table name not blank.');

				// OK were, going to need access to the datab ase
				$db = JFactory::getDbo();
				
				// Build our SQL to recreate the table on the remote server.
				// 1. First we drop the existing table
				$buildTableSQL.= 'DROP TABLE IF EXISTS '.$db->nameQuote($table).';';
				
				// 2. Then we create it again :D
				$db->setQuery('SHOW CREATE TABLE '.$table);
				$createStatement = $db->loadRow();
				$buildTableSQL.= "\n\n".$createStatement[1].";\n\n";
				
				// 3. Next we try and get the records in the table (after all no point in creating an insert statement if there are no records :D
				$db->setQuery('SELECT * FROM '.$table);
				if(($records = $db->loadRowList()) != null)
				{
					// 4. Then we build the list of field/column names that we'll insert data into
					// -- first we get the columns
					$tables = $db->getTableFields($table);
					$flds = $this->_getArrayOfFieldNames($tables);
					$num_fields = count($flds);
					
					// -- then we implode them into a suitable statement
					$columnSQL = 'INSERT INTO '.$table.' ('.implode( ', ' , $flds ).') VALUES ';
					
					$buildTableSQL.= $columnSQL;
					
					// 5. Now we can process the rows into INSERT values
					$valuesSQL = array();
					foreach ($records as $row) {
						$valuesSQL[] = '("'.implode(', ', $row).'")'; 
					}
					$valuesSQL = implode(', ', $valuesSQL);
				}

				$return.="\n\n\n";
				echo json_encode(array('msg' => JText::sprintf('%s copied successfully.', $table), 'status' => 1, 'data' => $return, 'log' => $log));
				return; // bounce out here
			}
		}
		// If we got here things didn't go well ;)
		echo json_encode(array('msg' => 'Table Copy - FAILED', 'status' => 0,  'data' => $return, 'log' => $log));
	}

	/**
	 * Strips out just the field names from the assoc array provided by Joomla!
	 * @param array $tables
	 * @return single list of field names 
	 */
	private function _getArrayOfFieldNames($tables)
	{
		
		$fieldNames = array();
		foreach ($tables as $tableName => $tableFields) {
			foreach ($tableFields as $aField => $aFieldType) {
				$fieldNames[] = $aField;
			}
		}
		return $fieldNames;
	}

	private function _getTablesForReplication($plan_id)
	{
		if(isset($plan_id))
		{
			$db = JFactory::getDbo();
			$db->setQuery("select * from `#__easystaging_tables` where `plan_id` = ".$plan_id." and `action` = '1'");
			if($tableRows = $db->loadAssocList()) {
				$tableResults = array();
				$tableResults['msg'] = JText::sprintf('Tables successfully retreived for Plan ID: %s (found %s tables)', $plan_id, count($tableRows));
				$tableResults['rows'] = $tableRows;
				$tableResults['status'] = count($tableRows);
				return $tableResults;
			} else {
				return array("msg" => JText::sprintf('Failed to retrieve tables from database (possibly no tables found for this plan: %s).', $plan_id), 'status' => 0);
			}
		}

		return array("msg" => JText::_('No Plan ID available to retrieve tables.'), 'status' => 0);
	}

	private function _createRSYNCExclusionFile($plan_id)
	{
		if(isset($plan_id))
		{
			// Load our site record
			JTable::addIncludePath(JPATH_ADMINISTRATOR.'/components/com_easystaging/tables');
			$Sites = JTable::getInstance('Site', 'EasyStagingTable');
			$site  = $Sites->load(array('plan_id'=>$plan_id, 'type'=>'1'));
			
			// Build our file path & file handle
			$pathToExclusionsFile = $this->_excl_file_path().$this->_excl_file_name();
			$result = array('fileName' => $this->_excl_file_name());
			$exclusionFile = fopen($pathToExclusionsFile, 'w');
			
			// Create the content for our exclusions file
			$defaultExclusions = <<< EOH
-tmp/
-logs/
-cache/
-configuration.php

EOH;

			// Combine the default exclusions with those in the local site record
			$allExclusions = $defaultExclusions.$this->_checkExclusionField($Sites->file_exclusions);
			$result['fileData'] = $allExclusions;
			
			// Attempt to write the file
			$result['status'] = fwrite($exclusionFile, $allExclusions);
			$result['msg'] = $result['status'] ? JText::sprintf('File written successfully (%s bytes)',$result['status']) : JText::_('Failed to write exclusions file') ;
			
			// Time to close off
			fclose($exclusionFile);
			
			// Return to Maine, where the moose, deer, eagles and loons roam.
			return $result;
		}

		return false;
	}

	private function _excl_file_path()
	{
		return JPATH_ADMINISTRATOR.'/components/com_easystaging/syncfiles/';
	}
	private function _excl_file_name()
	{
		return ('plan-'.$this->_plan_id().'-exclusions.txt');
	}
	
	private function _plan_id()
	{
		if(isset($plan_id)) {
			return $plan_id;
		} else {
			$jinput =  JFactory::getApplication()->input;
			$plan_id = $jinput->get('plan_id', 0, 'INT');
			return $plan_id;
		}
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
		$opts = 'avr';
		return ' -'.$opts;
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
