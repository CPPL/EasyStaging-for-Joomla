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

	function __construct($config)
	{

		require_once JPATH_COMPONENT.'/helpers/plan.php';
		parent::__construct($config);
	}

	function hello()
	{
		// Check for request forgeries
		if ($this->_tokenOK() && ($plan_id = $this->_plan_id()) && $this->_areWeAllowed($plan_id))
		{
			// It's alllll good...
			$runTicket = $plan_id . '-' . date('YmdHi');
			$runTicketDirectory = $this->_get_run_directory($runTicket);
			if (is_array($runTicketDirectory))
			{
				echo json_encode($runTicketDirectory);
			}
			else
			{
				$msg = JText::_( 'COM_EASYSTAGING__EASYSTAGING_IS_READY' );
				if ($this->_writeToLog($msg, $runTicket))
				{
					echo json_encode(array('msg' => $msg, 'status' => 1, 'runTicket' => $runTicket));
				}
				else
				{
					echo json_encode(array('msg' => JText::sprintf('COM_EASYSTAGING_JSON_UNABLE_TO_LOG', __FUNCTION__), 'status' => 0));
				}
			}
		}
		else
		{
			echo json_encode(array('msg' => JText::_( 'COM_EASYSTAGING_PLAN_ID_TOKE_DESC' ) , 'status' => 0));
		}
	}

	function setupRsync()
	{
		// Check for request forgeries
		if ($this->_tokenOK() && ($plan_id = $this->_plan_id()) && $this->_areWeAllowed($plan_id))
		{
			$rsResult = $this->_createRSYNCExclusionFile($plan_id);
			if ($rsResult['status'])
			{
				$fileCreated = $rsResult['fileName'];
				$msg = JText::sprintf('COM_EASYSTAGING_JSON_RSYNC_STEP_SUCCEEDED', $fileCreated);
				if ($this->_writeToLog($msg))
				{
					echo json_encode(array('msg' => $msg, 'status' => 1, 'data' => $rsResult));
				}
				else
				{
					echo json_encode(array('msg' => JText::sprintf('COM_EASYSTAGING_JSON_UNABLE_TO_LOG', __FUNCTION__), 'status' => 0));
				}
			}
			else
			{
				echo json_encode(array('msg' => JText::sprintf('COM_EASYSTAGING_JSON_RSYNC_STEP_FAILED', $plan_id), 'status' => 0,  'data' => $rsResult));
			}
		}
	}

	function runRsync()
	{
		// Check for request forgeries
		if ($this->_tokenOK() && ($plan_id = $this->_plan_id()) && $this->_areWeAllowed($plan_id))
		{
			// first we add the rsync options
			$rsyncCmd = 'rsync '.$this->_getRsyncOptions($plan_id);
			// then we add the exclusions file name
			$rsyncCmd.= ' --exclude-from='.$this->_getInputVar('fileName');

			// add the source
			$rsyncCmd.= ' ' . PlanHelper::getLocalSite($plan_id)->site_path;
			// add the destination
			$rsyncCmd.= ' ' . PlanHelper::getRemoteSite($plan_id)->site_path;

			// exec the rsync command
			$rsyncResult = '';
			$rsyncOutput = array();
			exec($rsyncCmd, $rsyncOutput, $rsyncResult);
			// check the result
			if ($rsyncResult == 0)
			{
				$msg = JText::sprintf('COM_EASYSTAGING_RSYNC_RUN_STATUS_OK', $plan_id)."\n";
			}
			else
			{
				$msg = JText::sprintf('COM_EASYSTAGING_RSYNC_RUN_STATUS_FAILED', $rsyncResult, $plan_id)."\n";
			}
			// write it all to the log and returned json
			$rsyncOutput[] = '<br />'.$rsyncCmd;
			$msg .= JText::sprintf('COM_EASYSTAGING_RSYNC_RUN_DESC',$plan_id);
			if ($this->_writeToLog($msg . "\n" . print_r($rsyncOutput,true)))
			{
				echo json_encode(array('msg' => $msg, 'status' => 1, 'data' => $rsyncOutput));
			}
			else
			{
				echo json_encode(array('msg' => JText::sprintf('COM_EASYSTAGING_JSON_UNABLE_TO_LOG', __FUNCTION__), 'status' => 0));
			}
		}
	}

	/**
	 * Check the connection to the remote database ...
	 */
	function checkDBConnection()
	{
		// Check for request forgeries
		if ($this->_tokenOK() && ($plan_id = $this->_plan_id()) && $this->_areWeAllowed($plan_id))
		{
			// Get the remote site details
			$rs = PlanHelper::getRemoteSite($plan_id);
			$options = array(
				'host'		=> $rs->database_host,
				'user'		=> $rs->database_user,
				'password'	=> $rs->database_password,
				'database'	=> $rs->database_name,
				'prefix'	=> $rs->database_table_prefix,
			);

			$rDBC = JDatabase::getInstance($options);

			if ($rDBC->getErrorNum() == 0)
			{
				$q = "SHOW VARIABLES LIKE 'max_allowed_packet'";
				$rDBC->setQuery($q);
				$qr = $rDBC->loadRow();
				$max_ps = $qr[1] * 0.95; // use slightly less than actual max to avoid the CSUA doublebyte issue...
				$session = JFactory::getSession();
				$session->set('com_easystaging_max_ps', $max_ps);
				$msg = JText::_( 'COM_EASYSTAGING_DATABASE_STEP_01_CONNECTED' );
				$remoteTablesRetreived = $this->_getRemoteDBTables($rDBC);
				if ($this->_writeToLog($msg . "\n" . print_r($remoteTablesRetreived,true)))
				{
					$session->set('com_easystaging_remoteTableList', $remoteTablesRetreived);
					echo json_encode(array('msg' => $msg, 'status' => 1, 'data' => $remoteTablesRetreived));
				}
				else
				{
					echo json_encode(array('msg' => JText::sprintf('COM_EASYSTAGING_JSON_UNABLE_TO_LOG', __FUNCTION__), 'status' => 0));
				}
			}
			else
			{
				echo json_encode(array('msg' => JText::_('COM_EASYSTAGING_DATABASE_STEP_01_FAILED_TO_CONNECT'), 'status' => 0, 'data' => $rDBC->getErrorMsg(true)));
			}
		}
	}

	/**
	 * Build and return a json data block with the tables to be copied...
	 */
	function getDBTables()
	{
		// Check for request forgeries
		if ($this->_tokenOK() && ($plan_id = $this->_plan_id()) && $this->_areWeAllowed($plan_id))
		{
			// Get list of tables we'll be acting on
			$session = JFactory::getSession();
			$rtl = $session->get('com_easystaging_remoteTableList','');
			if($rtl == '')
			{
				$remoteTableList = $this->_getInputVar('remoteTableList');
			}
			else
			{
				$remoteTableList = $rtl;
			}
			$tableResults = $this->_getTablesForReplication($plan_id, $remoteTableList);
			if ($tableResults)
			{
				$response = array(
					'msg'			=> JText::_('COM_EASYSTAGING_DATABASE_STEP_02_TABLES_LIST'),
					'status'		=> 1,
					'data'			=> $tableResults,
					'tablesFound'	=> count($tableResults['rows']),
				);
				$initialTableResults = $this->_getTablesForInitialReplication($plan_id);
				if ($initialTableResults['status'] != '0')
				{
					$response['msg'] = $response['msg'] . '<br />' . JText::sprintf('COM_EASYSTAGING_FOUND_TABLES_FO_DESC',count($initialTableResults['rows']));
					$response['initialCopyTables'] = $initialTableResults['rows'];
				}
				else
				{
					$response['msg'] = $response['msg'] . '<br />' . JText::_('COM_EASYSTAGING_NO_TABLES_FO_DESC');
				}

			}
			else
			{
				$response = array(
					'msg'		=> JText::_( 'COM_EASYSTAGING_DATABASE_STEP_02_FAILED' ),
					'status'	=> 0,
					'data'		=> $tableResults,
				);
			}
		}
		else
		{
			$response = array(
				'msg' => JText::_( 'COM_EASYSTAGING_PLAN_ID_TOKE_DESC' ),
				'status' => 0,
			);
		}
		// Log it...
		$this->_writeToLog($response['msg']);
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
		if ($this->_tokenOK() && ($plan_id = $this->_plan_id()) && $this->_areWeAllowed($plan_id))
		{
			$log = JText::_('COM_EASYSTAGING_TOKEN_PLAN_VALID');

			$jinput =  JFactory::getApplication()->input;
			$table = $jinput->get('tableName', '');
			// For each table we need to treat it like a database dump so that forgein keys etc don't cause issues
			$buildTableSQL = 'SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT' .
							"\n\n-- End of Statement --\n\n" .
							'SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS' .
							"\n\n-- End of Statement --\n\n" .
							'SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION' .
							"\n\n-- End of Statement --\n\n" .
							'SET NAMES utf8' .
							"\n\n-- End of Statement --\n\n" .
							'SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0' .
							"\n\n-- End of Statement --\n\n" .
							'SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE=\'NO_AUTO_VALUE_ON_ZERO\'' .
							"\n\n-- End of Statement --\n\n" .
							'SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0' .
							"\n\n-- End of Statement --\n\n";

			if ($table != '')
			{
				// OK were, going to need access to the database
				$db = JFactory::getDbo();
				$dbTableName = $db->quoteName($table);
				$hasAFilter = $this->_filterTable($table);

				// Build our SQL to recreate the table on the remote server.
				// Disable keys and Lock our table before replacing it and then unlock and enable keys after.
				$startSQL = "LOCK TABLES `$table` WRITE;\n" .
				"\n\n-- End of Statement --\n\n" .
				"ALTER TABLE `$table` DISABLE KEYS;\n" .
				"\n\n-- End of Statement --\n\n";
				$buildTableSQL .= $this->_changeTablePrefix($startSQL);

				// 1. First we drop the existing table
				$buildTableSQL.= 'DROP TABLE IF EXISTS '.$dbTableName.";\n\n-- End of Statement --\n\n";

				// 2. Then we create it again, except with a new prefix :D
				$db->setQuery('SHOW CREATE TABLE '.$dbTableName);
				$createStatement = $db->loadRow();
				$buildTableSQL.= str_replace("\r","\n",$createStatement[1]).";\n\n-- End of Statement --\n\n";
				// Ok a bit of search and replace to upate the prefix.
				$buildTableSQL = $this->_changeTablePrefix($buildTableSQL);

				// 3. Next we try and get the records in the table (after all no point in creating an insert statement if there are no records :D )
				$dbq = $db->getQuery(true); // Get a new JDatabaseQuery object
				$dbq->select('*');          // Set our select, in this case all fields
				$dbq->from($table);         // Set our table from which we're getting data

				if ($hasAFilter)             // If our table has an exclusion filter we need to add a 'where' element to our query.
				{
					$fieldToCompare = key($hasAFilter);
					$valueToAvoid = $hasAFilter[$fieldToCompare];
					$condition = $db->quoteName($fieldToCompare) . 'NOT LIKE \'%' . $valueToAvoid . '%\'';
					$dbq->where($condition);
				}
				$db->setQuery($dbq);

				if (($records = $db->loadRowList()) != null)
				{
					// 4. Then we build the list of field/column names that we'll insert data into
					// -- first we get the columns
					$tableFields = $db->getTableColumns($table);
					$flds = $this->_getArrayOfFieldNames($tableFields);
					// No problems getting the field names?
					if($flds)
					{
						$log.= '<br />'.JText::sprintf('COM_EASYSTAGING_CREATING_INSERT_STATEMEN_DESC',count($records));
						// -- then we implode them into a suitable statement
						$columnInsertSQL = 'INSERT INTO '.$this->_changeTablePrefix($dbTableName).' ('.implode( ', ' , $flds ).') VALUES ';

						// - keeping it intact for later user if the table is too big.

						// 5. Now we can process the rows into INSERT values
						// -- first we need to retreive the max_packet value from our session so we can figure out how many rows we can fit in to each chunk
						$session = JFactory::getSession();
						$max_ps = $session->get('com_easystaging_max_ps');
						// -- and we initialise our counter
						$sizeOfSQLBlock = strlen($columnInsertSQL);
						// -- then create an empty array ready for our values
						$valuesSQL = array();

						foreach ($records as $row)
						{
							// Process each row for slashes, new lines.
							foreach ($row as $field => $value)
							{
								$row[$field] = addslashes($value);
								$row[$field] = str_replace("\n","\\n",$row[$field]);
							}
							// Convert our row to a suitable values string
							$rowAsValues  = "('". implode('\', \'', $row) ."')";
							// First up we check to see if this row will put our SQL block size over our max_packet value on the remote server
							$rowSize = strlen($rowAsValues);

							if ($max_ps < ($sizeOfSQLBlock += $rowSize))
							{
								$buildTableSQL .= $columnInsertSQL . "\n" . implode(', ', $valuesSQL) . ";\n\n-- End of Statement --\n\n";
								$valuesSQL = array();
								$sizeOfSQLBlock = strlen($columnInsertSQL);
							}
							// We can add the processed & imploded row to our values array.
							$valuesSQL[] = $rowAsValues;
						}

						// We have some left over rows we need to add.
						if (count($valuesSQL))
						{
							$buildTableSQL .= $columnInsertSQL . "\n" . implode(', ', $valuesSQL) . ";\n\n-- End of Statement --\n\n";
						}

						// Time to unlock and restore keys to their enabled state
						$endofSQL = "ALTER TABLE `$table` ENABLE KEYS;\n" .
							"\n\n-- End of Statement --\n\n" .
							"UNLOCK TABLES;" .
							"\n\n-- End of Statement --\n\n" .
							'SET SQL_NOTES=@OLD_SQL_NOTES' .
							"\n\n-- End of Statement --\n\n" .
							'SET SQL_MODE=@OLD_SQL_MODE' .
							"\n\n-- End of Statement --\n\n" .
							'SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS' .
							"\n\n-- End of Statement --\n\n" .
							'SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT' .
							"\n\n-- End of Statement --\n\n" .
							'SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS' .
							"\n\n-- End of Statement --\n\n" .
							'SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION' .
							"\n\n-- End of Statement --\n\n";
						$endofSQL = $this->_changeTablePrefix($endofSQL);

						$buildTableSQL .= $endofSQL;
						// 6. Save the export SQL to file for the next request to execute.
						// Build our file path & file handle
						$pathToSQLFile = $this->_sync_files_path() . $this->_get_run_directory() . '/' . $this->_export_file_name($table);
						$data = $pathToSQLFile;
						if ($exportSQLFile = @fopen($pathToSQLFile, 'w'))
						{
							// Attempt to write the file
							$status = fwrite($exportSQLFile, $buildTableSQL);
							// Time to close off
							fclose($exportSQLFile);
							$msg = JText::sprintf('COM_EASYSTAGING_SQL_EXPORT_SUCC', $table);
							$response = array(
								'msg'			=> $msg,
								'status'		=> $status,
								'data' 			=> $data,
								'tableName' 	=> $table,
								'pathToSQLFile'	=> $pathToSQLFile,
								'log'			=> $log,
							);
						}
						else
						{
							$response = array(
								'msg'			=> JText::_('COM_EASYSTAGING_JSON_FAILED_TO_OPEN_SQL_EXP_FILE'),
								'status'		=> $exportSQLFile,
								'data'			=> error_get_last(),
								'tableName'		=> $table,
								'pathToSQLFile'	=> $pathToSQLFile,
								'log'			=> $log,
							);
						}
					}
					else
					{
						/**
						 * Ahh... bugger, Joomla! found a column name it didn't like (i.e. a column name that the current db doesn't like)
						 * Typical causes are columns names that start with a number or other illegal character or are completely numeric
						 *
						 */
						$response = array(
							'msg'		=> JText::_('COM_EASYSTAGING_TABLE_CONTAINS_INVALID_COLS_NAMES'),
							'status'	=> 0,
							'data'		=> $tableFields,
							'log'		=> $log);
					}
				}
				else
				{
					$log.= '<br />'.JText::sprintf('COM_EASYSTAGING_JSON__S_IS_EMPTY_NO_INS_REQ', $table);
					$response = array(
						'msg'		=> JText::sprintf('COM_EASYSTAGING_JSON__S_IS_EMPTY_NO_INS_REQ', $table),
						'status'	=> 0,
						'data'		=> '',
						'log'		=> $log);
				}

			}
			else
			{
				// If we got here things didn't go well ;)
				$response = array(
					'msg'		=> JText::_('COM_EASYSTAGING_TABLE_COPY_FAILED'),
					'status'	=> 0,
					'data'		=> $table,
					'log'		=> $log);
			}
		}
		else
		{
			$response = array('msg' => JText::_( 'COM_EASYSTAGING_PLAN_ID_TOKE_DESC' ) , 'status' => 0, 'data' => array());
		}
		echo json_encode($response);
		// Log it...
		$this->_writeToLog($response['msg']);
	}

	function runTableExport()
	{
		// Check for request forgeries
		$response = array();
		$response['status'] = 0;
		if ($this->_tokenOK() && ($plan_id = $this->_plan_id()) && $this->_areWeAllowed($plan_id))
		{
			$finishing = false;
			$pathToSQLFile = $this->_getInputVar('pathToSQLFile','');
			$tableName = $this->_getInputVar('tableName', '');
			// Make sure our file exists
			if (($pathToSQLFile != '') && (file_exists($pathToSQLFile)))
			{
				$response['msg'] = JText::sprintf('COM_EASYSTAGING_JSON_FOUND_SQL_EXPOR_FILE',$tableName);
				$exportSQLQuery = explode("\n\n-- End of Statement --\n\n", file_get_contents($pathToSQLFile));
				if (count($exportSQLQuery))
				{
					// Open DB connection.
					$rs = PlanHelper::getRemoteSite($plan_id);
					$options	= array(
						'host'		=> $rs->database_host,
						'user'		=> $rs->database_user,
						'password'	=> $rs->database_password,
						'database' => $rs->database_name,
						'prefix' => $rs->database_table_prefix,
					);

					$rDBC = JDatabase::getInstance($options);

					if ($rDBC)
					{
						$last_word = '';
						// Run queries from the SQL file.
						foreach ($exportSQLQuery as $query)
						{
							if (!empty($query))
							{
								list($first_word) = explode(' ', trim($query));
								$rDBC->setQuery($query);
								if ($rDBC->query())
								{
									if (($first_word == 'SET' && $last_word == 'UNLOCK') || ($first_word == 'SET' && $finishing))
									{
										$first_word = 'UNSET';
										$finishing = true;
									}
									if (($first_word == 'SET' && $last_word != 'SET') || ($first_word == 'UNSET' && $last_word != 'UNSET') || ($first_word != 'SET' && $first_word != 'UNSET'))
									{
										$response['msg'] .= '<br />'.JText::sprintf('COM_EASYSTAGING_JS_TABLE_EXPORT_QUERY_'.strtoupper($first_word), $tableName, $rs->database_name);
										$response['status'] = 1;
									}
									$last_word = $first_word;
								}
								else
								{
									$response['msg'] .= '<br />'.JText::sprintf('COM_EASYSTAGING_JS_TABLE_FAILED_EXPORT_QUERY_'.strtoupper($first_word), $tableName, $rDBC->getErrorMsg());
								}
							}
						}
					}
					/*
					 * @todo Confirm result, how? Check a matching number of records? What else? Maybe check the create statement?.
					 */
				}
				else
				{
					$response['msg'] = JText::sprintf('COM_EASYSTAGING_JSON_FAILED_TO_READ_SQL_FILE',$tableName,$pathToSQLFile);
					$response['status'] = 0;
				}
			}
			else
			{
				$response['msg'] = JText::sprintf('COM_EASYSTAGING_JSON_COULDNT_FIND_SQL_FILE',$tableName,$pathToSQLFile);
				$response['status'] = 0;
			}

			echo json_encode($response);
		}

		// Log it...
		$this->_writeToLog($response['msg']);

		return false;
	}

	function finishRun()
	{
		// Check for request forgeries
		if ($this->_tokenOK() && ($plan_id = $this->_plan_id()) && $this->_areWeAllowed($plan_id))
		{
			// Load our plan record
			JTable::addIncludePath(JPATH_ADMINISTRATOR.'/components/com_easystaging/tables');
			$Plan = JTable::getInstance('Plan', 'EasyStagingTable');

			if ($Plan->load(array('id'=>$plan_id)))
			{
				// Initialise variables.
				$date = JFactory::getDate();
				$Plan->last_run = $date->toSql();
				$Plan->store();
				$format = JText::_('DATE_FORMAT_LC2');
				$msg = JText::sprintf('COM_EASYSTAGING_LAST_RUN',$date->format($format,true));
				$result = array('msg' => $msg);
				// Log it...
				$this->_writeToLog($result['msg']);

				// Archive our work
				$zipArchiveName = $this->_sync_files_path() . '/' . $this->_get_run_directory() . '.zip';
				$folder = $this->_sync_files_path() . '/' . $this->_get_run_directory();
				$files_to_be_zipped = PlanHelper::directoryToArray($folder);
				if (PlanHelper::createZip($files_to_be_zipped, $zipArchiveName, $this->_sync_files_path()))
				{
					$result['cleanupMsg'] = JText::sprintf('COM_EASYSTAGING_PLAN_JSON_COMPRESSED_FILES', count($files_to_be_zipped), $zipArchiveName);
					// Clean up our work
					PlanHelper::remove_this_directory($folder);
				}
				else
				{
					$result['cleanupMsg'] = JText::_('COM_EASYSTAGING_PLAN_JSON_UNABLE_TO_ZIP_ERROR');
				}

				// Reply to user
				echo json_encode( $result );
			}
			return;
		}

		return false;
	}

	/**
	 * Looks for table name in our hard-coded filters array.
	 * @param string $tablename
	 * @return array if filter exists | false if not
	 */
	private function _filterTable($tablename)
	{
		$localPrefix = PlanHelper::getLocalSite($this->_plan_id())->database_table_prefix; // we don't want to remove the underscore
		$filters = array(
			$localPrefix . 'assets'		=> array('name' => 'com_easystaging%'),
			$localPrefix . 'extensions'	=> array('element' => 'com_easystaging'),
			$localPrefix . 'menu'		=> array('alias' => 'easystaging'),
		);

		if (array_key_exists($tablename, $filters))
		{
			return $filters[$tablename];
		}
		else
		{
			return false;
		}
	}

	private function _getRemoteDBTables($db)
	{
		/** @var $db JDatabase */
		$tableList = $db->getTableList();

		return $tableList;
	}

	/**
	 * Strips out just the field names from the assoc array provided by Joomla!
	 * @param   array  $tableFields
	 * @return  array  single list of field names
	 */
	private function _getArrayOfFieldNames($tableFields)
	{

		$db = JFactory::getDbo();
		$fieldNames = array();
		foreach ($tableFields as $aField => $aFieldType)
		{
			if(!is_numeric($aField) && ($thisFldName = $db->quoteName($aField)) && is_string($thisFldName))
			{
				$fieldNames[] = $thisFldName;
			}
			else
			{
				// Time to bail Joomla! considers the column name invalid for this DB.
				return false;
			}
		}
		return $fieldNames;
	}

	private function _changeTablePrefix($sql)
	{
		$localSite = PlanHelper::getLocalSite($this->_plan_id());
		$localPrefix = $localSite->database_table_prefix;
		$remoteSite = PlanHelper::getRemoteSite($this->_plan_id());
		$remotePrefix = $remoteSite->database_table_prefix;
		return str_replace($localPrefix, $remotePrefix, $sql);
	}

	private function _getTablesForReplication($plan_id, $remoteTableList = array())
	{
		if (isset($plan_id))
		{
			$db = JFactory::getDbo();
			$query = $db->getQuery(true);
			$query->select('*');
			$query->from($db->quoteName('#__easystaging_tables'));
			$query->where($db->quoteName('plan_id') . ' = ' . $db->quote($plan_id));
			$query->where('(' . $db->quoteName('action') . ' = ' . $db->quote('1') . ' OR ' . $db->quoteName('action') . ' = ' . $db->quote('2') . ')');
			$query->order($db->quoteName('tablename'));
			$db->setQuery($query);

			if ($localTableRows = $db->loadAssocList())
			{
				$tableRows = array(); // Where we'll store the tables to be copied.
				$localPrefix = PlanHelper::getLocalSite($plan_id)->database_table_prefix;   // Get the local & remote prefix for use in the loop
				$remotePrefix = PlanHelper::getRemoteSite($plan_id)->database_table_prefix; // Get the local & remote prefix for use in the loop
				// Loop through the table settings and adding them to the $tableRows to be used if their action suites.
				foreach ($localTableRows as $localTable)
				{
					// If this table is set to 'Copy To Live' we add it to our array
					if ($localTable['action'] == 1)
					{
						$tableRows[] = $localTable;
					}
					else
					{
						// It's a copy if not exists table
						// Swap out the local table prefix with the remote, so we can get a match
						$itsRemoteTableName = str_replace($localPrefix, $remotePrefix, $localTable['tablename']);
						if (empty($remoteTableList) || !in_array($itsRemoteTableName, $remoteTableList))
						{
							$tableRows[] = $localTable;
						}
					}
				}

				$tableResults = array();
				$tableResults['msg'] = JText::sprintf('COM_EASYSTAGING_TABLES_SUCCESSFULLY_RETREIVE_FOR_PLAN', $plan_id, count($tableRows));
				$tableResults['rows'] = $tableRows;
				$tableResults['status'] = count($tableRows);
				return $tableResults;
			}
			else
			{
				return array(
					'msg'		=> JText::sprintf('COM_EASYSTAGING_FAILED_TO_RETRIEV_TABLES_FROM_DB', $plan_id),
					'status'	=> 0,
				);
			}
		}

		return array(
			'msg'		=> JText::_('COM_EASYSTAGING_NO_PLAN_ID_AVAIL'),
			'status'	=> 0,
		);
	}

	private function _getTablesForInitialReplication($plan_id)
	{
		if (isset($plan_id))
		{
			$db = JFactory::getDbo();
			$db->setQuery("select * from `#__easystaging_tables` where `plan_id` = ".$plan_id." and `action` = '2'");
			if ($tableRows = $db->loadAssocList())
			{
				$tableResults = array(
					'msg'		=> JText::sprintf('COM_EASYSTAGING_INITIAL_REPLICATION_TABLE_DESC', $plan_id, count($tableRows)),
					'rows'		=> $tableRows,
					'status'	=> count($tableRows),
				);
				return $tableResults;
			}
			else
			{
				$tableResults = array(
					'msg'		=> JText::sprintf('COM_EASYSTAGING_FAILED_TO_RETRIEV_DESC', $plan_id),
					'status'	=> 0,
				);
				return $tableResults;
			}
		}

		return array(
			'msg' => JText::_('COM_EASYSTAGING_NO_PLAN_ID_AVAIL'),
			'status' => 0,
		);
	}

	private function _createRSYNCExclusionFile($plan_id)
	{
		if (isset($plan_id))
		{
			// Build our file path & file handle
			$pathToExclusionsFile = $this->_get_run_directory() . '/' . $this->_excl_file_name();
			$result = array(
				'fileName' =>  $pathToExclusionsFile,
			);
			$result['fullPathToExclusionFile'] = $this->_sync_files_path() . $pathToExclusionsFile;

			if ($exclusionFile = @fopen($result['fullPathToExclusionFile'], 'w'))
			{

				// Create the content for our exclusions file
				$defaultExclusions = <<< DEF
- com_easystaging/
- /administrator/language/en-GB/en-GB.com_easystaging.ini
- /tmp/
- /logs/
- /cache/
- /administrator/cache/
- /configuration.php
- /.htaccess

DEF;
				// Get local site record
				$localSite = PlanHelper::getLocalSite($plan_id);

				// Combine the default exclusions with those in the local site record
				$allExclusions = $defaultExclusions . trim($this->_checkExclusionField($localSite->file_exclusions));
				$result['fileData'] = $allExclusions;

				// Attempt to write the file
				$result['status'] = fwrite($exclusionFile, $allExclusions);
				$result['msg'] = $result['status'] ? JText::sprintf('COM_EASYSTAGING_FILE_WRITTEN_SUCCESSFULL_DESC',$result['status']) : JText::_('COM_EASYSTAGING_FAILED_TO_WRIT_DESC') ;

				// Time to close off
				fclose($exclusionFile);
			}
			else
			{
				$result['status'] = 0;
				$result['msg'] = JText::_('COM_EASYSTAGING_JSON_UNABLE_TO_OPEN_RSYNC_EXC_FILE');
			}
			// Return to Maine, where the moose, deer, eagles and loons roam.
			return $result;
		}
		return false;
	}

	private function _writeToLog($logLine, $runTicket = NULL)
	{
		if ($runTicket == NULL)
		{
			$runTicket = $this->_getInputVar('runTicket');
		} // first call for a plan run may need to supply a ticket otherwise retrieve from request values.
		$logFileName = 'es-log-plan-' . $this->_plan_id() . '-run-' . $runTicket . '.txt';
		$fullPathToLogFile = JPATH_ADMINISTRATOR . '/components/com_easystaging/syncfiles/' . $runTicket . '/' . $logFileName;

		if ($logFile = fopen($fullPathToLogFile, 'ab'))
		{
			// 'ab' has 'b' for windows :D
			$logWriteResult = fwrite($logFile, $logLine . "\n");
			return $logWriteResult;
		}

		return false;
	}

	private function _sync_files_path()
	{
		return JPATH_ADMINISTRATOR . '/components/com_easystaging/syncfiles/';
	}
	private function _get_run_directory($runDirectory = NULL)
	{
		// Get location files from this run will be saved in to.
		if ($runDirectory == NULL)
		{
			$runDirectory = $this->_getInputVar('runTicket') ;
		}

		$runDirectoryPath = JPATH_ADMINISTRATOR . '/components/com_easystaging/syncfiles/' . $runDirectory;

		if ($runDirectory)
		{
			if (!file_exists($runDirectoryPath))
			{
				if (mkdir($runDirectoryPath, 0777, true))
				{
					return $runDirectory;
				}
				else
				{
					$result['status'] = 0;
					$result['msg'] = JText::sprintf('COM_EASYSTAGING_PLAN_JSON_UNABLE_TO_CREAT_RUN_DIR', $runDirectoryPath);
				}
			}
			else
			{
				return $runDirectory;
			}
		}
		else
		{
			$result['status'] = 0;
			$result['msg'] = JText::_('COM_EASYSTAGING_PLAN_JSON_NO_VALID_RUN_TICKET');
		}
		return $result;
	}
	private function _excl_file_name()
	{
		return ('plan-'.$this->_plan_id().'-exclusions.txt');
	}
	private function _export_file_name($table)
	{
		return ('plan-'.$this->_plan_id().'-'.$table.'-export.sql');
	}
	private function _rsync_output_file_name($table)
	{
		return ('plan-'.$this->_plan_id().'-'.$table.'-rsyncout.txt');
	}

	private function _plan_id()
	{
		if (isset($plan_id))
		{
			return $plan_id;
		}
		else
		{
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
	 * Checks $file_exclusions to ensure each line starts with a "-" or "+" as required by rsync ...
	 *
	 * @param string $file_exclusions
	 *
	 * @return string|boolean - false on failure
	 */
	private function _checkExclusionField($file_exclusions)
	{
		if (isset($file_exclusions) && ($file_exclusions != ''))
		{
			$result = array();
			$file_exclusions = explode("\n", str_replace("\r\n", "\n", $file_exclusions)); // Just in case, we convert all \r\n before exploding
			foreach ($file_exclusions as $fe_line)
			{
				$fe_line = trim($fe_line);
				// Check for explicit include or exclude because some rsyncs are broken (assume exclusion)
				if (($fe_line[0] != '-') && ($fe_line[0] != '+'))
				{
					$fe_line = '- '.$fe_line;
				}
				$result[] = $fe_line;
			}
			return implode("\n", $result);
		}
		else
		{
			return false;
		}
	}

	private function _getRsyncOptions($plan_id)
	{
		//place holder, will get from plan record
		$SiteRecord = PlanHelper::getLocalSite($plan_id);
		$opts = $SiteRecord->rsync_options;
		return $opts;
	}

	/**
	 * Confirm the current request has a valid token.
	 *
	 * @return bool
	 */
	private function _tokenOK()
	{
		// Check for request forgeries
		if (!JRequest::checkToken('request'))
		{
			// We are stuck with this until JInput catches up.
			$response = array(
						'status' => '0',
						'msg' => JText::_('JINVALID_TOKEN')
			);
			echo json_encode($response);
			return false;
		}

		return true;
	}

	/**
	 * Make sure the currently logged in user is allowed to run plans
	 *
	 * @param $plan_id
	 *
	 * @return boolean
	 *
	 * @since 1.0
	 */
	private function _areWeAllowed($plan_id)
	{
		// Should we be here?
		$canDo = PlanHelper::getActions($plan_id);
		return $canDo->get('easystaging.run');
	}

	/**
	 * Is this process id ($pid) running?
	 *
	 * @param   int  $pid  The process id to check for
	 *
	 * @return bool
	 *
	 * @since  1.1
	 */
	private function _isRunning($pid)
	{
		try{
			$result = shell_exec(sprintf("ps %d", $pid));
			if( count(preg_split("/\n/", $result)) > 2)
			{
				return true;
			}
		}catch(Exception $e)
		{
			//nothing to do... yet
		}
		return false;
	}
}
