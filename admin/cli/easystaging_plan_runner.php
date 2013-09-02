<?php
/**
 * @package    EasyStaging.Cli
 * @author     Craig Phillips <craig@craigphillips.biz>
 * @copyright  Copyright (C) 2012 Craig Phillips Pty Ltd.
 * @license    GNU General Public License version 3, or later
 * @url        http://www.seepeoplesoftware.com
 *
 * This is a cli script which should be executed from EasyStaging Pro.
 *
 */

// Make sure we're being called from the command line, not a web interface
if (array_key_exists('REQUEST_METHOD', $_SERVER)) die("Direct Web Request refused");

// Set flag that this is a parent file.
define('_JEXEC', 1);
define('DS', DIRECTORY_SEPARATOR);

error_reporting(E_ALL | E_NOTICE);
ini_set('display_errors', 1);

// Load system defines
if (file_exists(dirname(dirname(__FILE__)) . '/defines.php'))
{
	require_once dirname(dirname(__FILE__)) . '/defines.php';
}

if (!defined('_JDEFINES'))
{
	define('JPATH_BASE', dirname(dirname(__FILE__)));
	require_once JPATH_BASE . '/includes/defines.php';
}

require_once JPATH_LIBRARIES . '/import.php';
require_once JPATH_LIBRARIES . '/cms.php';

// Force library to be in JError legacy mode
JError::$legacy = true;

// Load the configuration
require_once JPATH_CONFIGURATION . '/configuration.php';

// Set the root path to EasyStaging
define('JPATH_COMPONENT_ADMINISTRATOR', JPATH_ADMINISTRATOR . '/components/com_easystaging');

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
 * This script will load the specified plan steps that remaining and execute them.
 *
 * @package  Joomla.CLI
 * @since    2.5
 */
class EasyStaging_PlanRunner extends JApplicationCli
{
	/**
	 * @var   JRegistry  $params
	 */
	private $params;

	/**
	 * @var   int  $_status
	 */
	private $status = 0;

	/**
	 * @var   string  $runticket
	 */
	protected $runticket;

	/**
	 * @var  int  $plan_id
	 */
	private   $plan_id;

	/**
	 * @var   EasyStagingTableSteps  $rootStep
	 */
	private $rootStep;

	/**
	 * @var   bool       $db_status
	 */
	private $db_status;

	/**
	 * @var   JDatabaseMySQL  $target_db
	 */
	private $target_db;

	/**
	 * @var   JDatabaseMySQL  $source_db
	 */
	private $source_db;

	/**
	 * @var EasyStagingTableSites
	 */
	private $target_site;

	/**
	 * @var EasyStagingTableSites
	 */
	private $source_site;

	/**
	 * @var   int  $max_ps
	 */
	private $max_ps;

	/**
	 * @var  array  $targetTablesRetreived
	 */
	private $targetTablesRetreived;

	private $logFile;

	/**
	 * Action types
	 *
	 * Root action for a run
	 */
	const RUN_ROOT = 0;
	/**
	 * Rsync Actions
	 * Eventually plans will have multiple rsync's available to them
	 */
	const RSYNC_PUSH  = 1;
	/**
	 *
	 */
	const RSYNC_PULL  = 2;
	const RSYNC_CLEAR = 3;

	/**
	 * Table Actions
	 */
	// Skip this Table
	const TABLE_DONT_COPY_IGNORE  = 10;
	// Copy to Live
	const TABLE_COPY_2_LIVE_ONLY  = 11;
	// Copy to Live, Only if not found.
	const TABLE_COPY_IF_NOT_FND   = 12;
	// Copy to Live, After Merge From Live
	const TABLE_MERGE_BACK_COPY   = 13;
	// Copy From Live (merges with existing)
	const TABLE_MERGE_BACK_ONLY   = 14;
	// Move From Live (merges with existing)
	const TABLE_MERGE_BACK_CLEAN  = 15;
	// Replace With Live (REPLACES existing with remote)
	const TABLE_COPY_BACK_REPLACE = 16;

	/**
	 * Table Actions
	 */
	const RUN_MSG      = 99;

	/**
	 * Plan is not published i.e. not to be used
	 */
	const UNPUBLISHED  = 0;
	/**
	 * Plan is published and can be used
	 */
	const PUBLISHED    = 1;
	/**
	 * Plan is running (must have been published).
	 */
	const RUNNING      = 2;
	/**
	 * Step states
	 */
	const WAITING      = 0;
	const FINISHED     = 1;
	const PROCESSING   = 2;
	/**
	 * Step Reported states
	 */
	const NOTREPORTED  = 0;
	const REPORTED     = 1;

	/**
	 * Entry point for the plan funner (yes it's more fun)
	 *
	 * @return  void
	 *
	 * @since   2.5
	 */
	public function doExecute()
	{
		// Load language files
		$lang = JFactory::getLanguage();
		$lang->load('com_easystaging', JPATH_ADMINISTRATOR);
		$lang->load('com_easystaging', JPATH_COMPONENT_ADMINISTRATOR);

		// Get our params
		jimport('joomla.application.component.helper');
		$component = JComponentHelper::getComponent('com_easystaging');

		$params = $component->params;
		$this->params = $params;

		// Let Joomla know where to look for JTables
		JTable::addIncludePath(JPATH_COMPONENT_ADMINISTRATOR . '/tables');

		// Ok, we're under way...
		$this->out('Plan Runner loaded...');

		$this->runticket = $this->input->getCmd('runticket', '', 'string');

		// Lets load any steps for the nominated plan run
		if ($this->runticket == '')
		{
			$this->out('No run ticket provided.');
		}
		else
		{
			if (RunHelper::runTicketIsValid($this->runticket))
			{
				// Find any overrides that may have been passed in...
				$overrides = $this->getOption('override', array(), false);

				// Get some basics
				$this->plan_id = $this->_plan_id();
				$this->target_site = PlanHelper::getRemoteSite($this->plan_id);
				$this->source_site = PlanHelper::getLocalSite($this->plan_id);

				if ($this->target_site && $this->source_site)
				{
					$steps = RunHelper::getRunSteps($this->runticket);

					// If we have steps lets process them
					if ($steps)
					{
						// Process each step
						foreach ($steps as $step)
						{
							// Get our matching EasyStagingTableSteps Obj
							$theStepObj = RunHelper::getStep($step['id']);

							switch ($step['action_type'])
							{
								case self::RUN_ROOT:
									// Keep the root step for closing off the run
									$this->rootStep = $theStepObj;
									$this->status = true;
									break;

								case self::RSYNC_PUSH:
								case self::RSYNC_PULL:
								case self::RSYNC_CLEAR:
									$this->status = $this->performRSYNC($theStepObj);
									break;

								case self::TABLE_DONT_COPY_IGNORE:
									// Nothing to do here...
									break;

								case self::TABLE_COPY_2_LIVE_ONLY:
								case self::TABLE_COPY_IF_NOT_FND:
								case self::TABLE_MERGE_BACK_COPY:
								case self::TABLE_MERGE_BACK_ONLY:
								case self::TABLE_MERGE_BACK_CLEAN:
								case self::TABLE_COPY_BACK_REPLACE:
									$this->status = $this->performTableStep($theStepObj);
									break;

								default:
									// Anything else we discard, who knows what crazyness caused this... best to avoid potential damage by doing nothing!
							}

							if (!$this->status)
							{
								// We've had a serious failure, no point in continuing with the loop.
								break;
							}
						}

						// Time to mark this as done
						$this->finishRun($this->_plan_id());
					}
					else
					{
						// Else we simply exit.
						$this->out('No steps found.');
					}
				}
				else
				{
					// Time to go boom!
					$this->out('Couldn\'t get local/remote site details.' );
				}
			}
		}
	}

	/**
	 * The main entry for RSYNC steps.
	 *
	 * @param   EasyStagingTableSteps  $theStep  The step object.
	 *
	 * @since 1.1.0
	 *
	 * @return  bool  Indicating success or failure.
	 */
	private function performRSYNC($theStep)
	{
		$status = false;

		// Create the RSYNC exclusions file
		$rsResult = $this->_createRSYNCExclusionFile($theStep);

		if ($rsResult['status'])
		{
			$msg = JText::sprintf('COM_EASYSTAGING_JSON_RSYNC_STEP_SUCCEEDED', $rsResult['fileName']);
			$this->_log($theStep, $msg);
			$status = $this->runRsync($theStep, $rsResult['fullPathToExclusionFile']);
			RunHelper::markCompleted($theStep, JText::_('COM_EASYSTAGING_CLI_RSYNC_PROCESS_COMPLETED'));
		}
		else
		{
			RunHelper::markCompleted($theStep, JText::sprintf('COM_EASYSTAGING_JSON_RSYNC_STEP_FAILED', $this->plan_id));
		}

		return $status;
	}

	/**
	 * Run the rsync
	 *
	 * @param   EasyStagingTableSteps  $theStep   The step.
	 *
	 * @param   string                 $filename  Path to the exclusion file.
	 *
	 * @throws  Exception
	 *
	 * @return  bool
	 */
	protected function runRsync($theStep, $filename)
	{
		$status = true;
		$details = json_decode($theStep->action);

		// First we add the rsync options
		$rsyncAction = $theStep->action_type;
		if (($rsyncAction == self::RSYNC_PULL) || ($rsyncAction == self::RSYNC_PUSH))
		{
			$rsyncCmd = 'rsync ' . $details->rsync_options;
		}
		elseif ($rsyncAction == self::RSYNC_CLEAR)
		{
			$rsyncCmd = 'rsync -avr';
		}
		else
		{
			$status = false;
		}

		if ($status)
		{
			// Then we add the exclusions file name
			$rsyncCmd .= ' --exclude-from=' . $filename;

			// Figure out our source site
			if($rsyncAction == self::RSYNC_PUSH)
			{
				$source_path = $details->local_site_path . $details->source_path;
				$target_path = $details->remote_site_path . $details->target_path;
			}
			else
			{
				$source_path = $details->remote_site_path . $details->source_path;
				$target_path = $details->local_site_path . $details->target_path;
			}

			// Add the source
			$rsyncCmd .= ' ' . $source_path;

			// Add the destination
			$rsyncCmd .= ' ' . $target_path;

			// Update the steps results
			$this->_log($theStep, JText::sprintf('COM_EASYSTAGING_CLI_RSYNC_CMD_X_Y', $details->label, $rsyncCmd));

			$rsyncResult = $this->runRsyncCmd($rsyncCmd, $theStep);
			// Did it end cleanly?
			if (($rsyncResult != false) && ($rsyncResult == 0))
			{
				// It ended cleanly.
				$status = true;
				$this->_log($theStep, JText::_('COM_EASYSTAGING_CLI_RSYNC_INDICATES_SUCCESS'));

				// Is it a MOVE file action?
				if ($rsyncAction == self::RSYNC_CLEAR)
				{
					$this->_log($theStep, JText::sprintf('COM_EASYSTAGING_CLI_RSYNC_CLEAN_X', $details->label));
					// We make an empty directory
					$emptyDir = $this->_run_files_path() . '/empty';
					mkdir($emptyDir, 0777, true);

					// We create our source (the empty dir) and the target (the original source on the remote)
					$source_path = $emptyDir . '/';
					$target_path = $details->remote_site_path . $details->source_path;

					// Build our rsync command
					$rsyncCmd = 'rsync -dv --delete ' . $source_path . ' ' . $target_path;

					// Run the command
					$rsyncResult = $this->runRsyncCmd($rsyncCmd, $theStep);
					$status = (($rsyncResult != false) && ($rsyncResult == 0)) ? true : false;
				}
			}
			else
			{
				$this->_log($theStep, JText::_('COM_EASYSTAGING_CLI_RSYNC_INDICATES_FAILURE'));
			}
		}

		return $status;
	}


	private function runRsyncCmd($rsyncCmd, $theStep)
	{
		// Assume failure
		$rsyncResult = false;

		// As this is a long running process we want to update the results text as we go...
		$descriptorspec = array(
			0 => array("pipe", "r"),
			1 => array("pipe", "w"),
			2 => array("pipe", "w")
		);

		// Get the rsyc exit code as the last line of output
		$rsyncCmd .= ' && echo $?';

		// Run the rsync command
		$rsync_process = proc_open($rsyncCmd, $descriptorspec, $pipes);

		// Where we're going to store the rsync output...
		$rsyncOutput = array();
		$rsyncOutput[] = JText::_('COM_EASYSTAGING_CLI_RSYNC_STARTING');
		$rsyncOutput[] = $rsyncCmd;

		if (is_resource($rsync_process))
		{
			while ($s = fgets($pipes[1]))
			{
				// $s is the latest string written to stdout by rsync
				$rsyncOutput[] = $s;
				$this->_log($theStep, $s);
			}

			$rsyncResult = end($rsyncOutput);
			proc_close($rsync_process);
		}
		else
		{
			$this->_log($theStep, JText::_('COM_EASYSTAGING_CLI_RSYNC_PROC_OPEN_FAILED'));
		}

		return $rsyncResult;
	}

	/**
	 * Builds the appropriate file of exclusion, inserting our defaults along the way.
	 *
	 * @param   EasyStagingTableSteps  $theStep  The current step.
	 *
	 * @return array
	 */
	private function _createRSYNCExclusionFile($theStep)
	{
		$this->_log($theStep, JText::_('COM_EASYSTAGING_CLI_START_RSYNC_STEP1'));
		$decoded_details = json_decode($theStep->action);

		// Build our file path & file handle
		$pathToExclusionsFile = $this->_get_run_directory() . '/' . $this->_excl_file_name($decoded_details->id);
		$result = array(
			'fileName' => $pathToExclusionsFile,
		);
		$result['fullPathToExclusionFile'] = $this->_sync_files_path() . $pathToExclusionsFile;

		if ($exclusionFile = @fopen($result['fullPathToExclusionFile'], 'w'))
		{
			// Create the content for our exclusions file
			$defaultExclusions = <<< DEF
- com_easystaging/
- /configuration.php
- /administrator/.htaccess
- /administrator/cache/
- /administrator/language/en-GB/en-GB.com_easystaging.ini
- /administrator/language/en-GB/en-GB.com_easystaging.sys.ini
- /cache/
- /cli/easystaging_plan_runner.php
- /logs/
- /tmp/
- /.htaccess
- .DS_Store

DEF;

			// Combine the default exclusions with those in the local site record
			$allExclusions = $defaultExclusions . trim($this->_checkExclusionField($decoded_details->file_exclusions));
			$result['fileData'] = $allExclusions;

			// Insert <br>'s into exclusions for display in browser
			$this->_log($theStep, $allExclusions . "\n");

			// Attempt to write the file
			$result['status'] = fwrite($exclusionFile, $allExclusions);
			$result['msg'] = $result['status'] ? JText::sprintf('COM_EASYSTAGING_FILE_WRITTEN_SUCCESSFULL_DESC', $result['status'])
												: JText::_('COM_EASYSTAGING_FAILED_TO_WRIT_DESC');

			// Time to close off
			fclose($exclusionFile);
		}
		else
		{
			$result['status'] = 0;
			$result['msg'] = JText::_('COM_EASYSTAGING_JSON_UNABLE_TO_OPEN_RSYNC_EXC_FILE');
		}

		$this->_log($theStep, $result['msg']);

		// Return to Maine, where the moose, deer, eagles and loons roam.
		return $result;
	}

	/**
	 * Checks $file_exclusions to ensure each line starts with a "-" or "+" as required by rsync ...
	 *
	 * @param   string  $file_exclusions  The exclusions to be checked and conformed.
	 *
	 * @return  string|boolean - false on failure
	 */
	private function _checkExclusionField($file_exclusions)
	{
		if (isset($file_exclusions) && ($file_exclusions != ''))
		{
			$result = array();

			// Just in case, we convert all \r\n before exploding
			$file_exclusions = explode("\n", str_replace("\r\n", "\n", $file_exclusions));

			foreach ($file_exclusions as $fe_line)
			{
				$fe_line = trim($fe_line);

				// Check for explicit include or exclude because some rsyncs are broken (assume exclusion)
				if (($fe_line[0] != '-') && ($fe_line[0] != '+'))
				{
					$fe_line = '- ' . $fe_line;
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


	/**
	 * TABLE SECTION
	 */

	/**
	 * The starting point for all table related steps.
	 *
	 * @param   EasyStagingTableSteps  $step  The table copying step.
	 *
	 * @return  null
	 */
	private function performTableStep($step)
	{
		// Assume failure
		$status = false;

		if (!($this->db_status))
		{
			$this->db_status = $this->checkDBConnection($step);
		}

		$this->_log($step, JText::sprintf('COM_EASYSTAGNG_CLI_STARTING_TABLE_X', $step->action));

		if ($this->db_status)
		{
			// Switch the step to the relevant method to handle the table.
			switch ($step->action_type)
			{
				case self::TABLE_COPY_2_LIVE_ONLY:
					$actiontype = 'COPY2LIVE';
					$status = $this->performTableCopy($step);
					break;
				case self::TABLE_COPY_IF_NOT_FND:
					$actiontype = 'COPYINF';
					$status = $this->performTableCopyINF($step);
					break;
				case self::TABLE_MERGE_BACK_COPY:
				case self::TABLE_MERGE_BACK_ONLY:
				case self::TABLE_MERGE_BACK_CLEAN:
					$actiontype = 'MERGE';
					$status = $this->performTableMerge($step);
					break;
				case self::TABLE_COPY_BACK_REPLACE:
					$actiontype = 'COPYBACK';
					$status = $this->performTableCopyBack($step);
					break;
			}
		}

		if ($status)
		{
			$msg = JText::sprintf('COM_EASYSTAGING_CLI_RESULT_FOR_TABLE_X_' . $actiontype . '_STEP_SUCCESS', $step->action);
		}
		else
		{
			$msg = JText::sprintf('COM_EASYSTAGING_CLI_RESULT_FOR_TABLE_X_' . $actiontype . '_STEP_FAILURE', $step->action);
		}

		$this->_log($step, $msg . "\n");
		RunHelper::markCompleted($step, '');

		return $status;
	}

	/**
	 * Copies a table only if it doesn't exist on the target database
	 *
	 * @param   EasyStagingTableSteps  $step  The table to be copied.
	 *
	 * @return bool
	 */
	private function performTableCopyINF($step)
	{
		// It's not a problem if the table does or doesn't exist on the target db.
		$status = true;

		// Create the target name
		$itstargetTableName = $this->swapTablePrefix($step->action);

		// Does the table exist on the target db?
		if (empty($this->targetTablesRetreived) || !in_array($itstargetTableName, $this->targetTablesRetreived))
		{
			// No matching table found, lets copy it!
			$status = $this->performTableCopy($step);
		}

		return $status;
	}

	/**
	 * The main table copying method.
	 *
	 * @param   EasyStagingTableSteps  $step  The step object for the table in question.
	 *
	 * @return  bool
	 */
	private function performTableCopy($step)
	{
		// Lets assume we fail
		$status = false;

		// First we export the table
		if ($this->createCopyTable_ExportFile($step))
		{
			// Run the table copy
			$status = $this->runTableCopyExport($step);
		}

		return $status;
	}

	/**
	 * The main entry table merge steps.
	 *
	 * @param   EasyStagingTableSteps  $step  The step object for the table in question.
	 *
	 * @return  bool
	 */
	private function performTableMerge($step)
	{
		// Setup some defaults
		$table = $step->action;
		$at = $step->action_type;

		// Perform the pull back
		$status = $this->performTableMergeBack($step);

		if ($status)
		{
			// Perform the push out
			if ($at == self::TABLE_MERGE_BACK_COPY)
			{
				$status = $this->performTableCopy($step);

				if ($status)
				{
					$msg = JText::sprintf('COM_EASYSTAGING_CLI_MERGE_BACK_COPY_SUCCESSFUL_X', $table);
				}
				else
				{
					$msg = JText::sprintf('COM_EASYSTAGING_CLI_MERGE_BACK_COPY_FAILED_X', $table);
				}
			}
			elseif (($at == self::TABLE_MERGE_BACK_CLEAN))
			{
				$deletedRows = $this->emptyTable($step);

				if ($deletedRows >= 0)
				{
					$msg = JText::sprintf('COM_EASYSTAGING_CLI_REMOTE_TABLE_EMPTIED_X_Y', $table, $deletedRows);
				}
				else
				{
					$msg = JText::sprintf('COM_EASYSTAGING_CLI_REMOTE_TABLE_FAILED_TO_EMPTY_X_Y', $table, $deletedRows);
				}
			}
			else
			{
				// TABLE_MERGE_BACK_ONLY
				$msg = JText::sprintf('COM_EASYSTAGING_CLI_MERGE_BACK_ONLY_SUCCESSFUL_X', $table);
			}
		}
		else
		{
			$msg = JText::sprintf('COM_EASYSTAGING_CLI_MERGE_FROM_X_FAILED_Y', $table, $status);
		}

		$this->_log($step, $msg);

		return $status;
	}

	/**
	 * REPLACES the source table with the matching target table.
	 *
	 * @param   EasyStagingTableSteps  $step  The step object for the table in question.
	 *
	 * @return  bool
	 */
	private function performTableCopyBack($step)
	{
		// Assume failure
		$status = false;

		// We're running in reverse so we need to swap our table prefix to that of the source i.e. remote table
		$step->action = $this->swapTablePrefix($step->action);
		$step->store();

		// Then we need to swap our source and target databases around
		$this->swapSourceTarget();

		/*
		 * Using the same export method to push a table out we get create the target tables export file
		 * Qs?
		 * 1. Filters shouldn't apply really...
		 * 2. Large tables?
		 */
		if ($this->createCopyTable_ExportFile($step))
		{
			// Run the table copy
			$status = $this->runTableCopyExport($step);
		}

		// Regardless of result we need to swap our db sources back.
		$this->swapSourceTarget();

		return $status;
	}

	/**
	 * Get the records from the remote DB's equivalent table and merges them with the local version.
	 *
	 * @param   EasyStagingTableSteps  $step  The current step.
	 *
	 * @return  bool
	 */
	private function performTableMergeBack($step)
	{
		// Assume the world works as expected.
		$status = true;

		// We need to swap our source and target databases because the merge step makes the remote the source
		$this->swapSourceTarget();

		// Get the table profiles
		$tableProfiles = $this->getTableProfile($step);

		if ($tableProfiles)
		{
			// Merge our table back
			if ($status = $this->doMergeRecords($step, $tableProfiles))
			{
				$msg = JText::sprintf('COM_EASYSTAGING_CLI_SUCCESSFULL_MERGE_FOR_TABLE_X', $step->action);
			}
			else
			{
				$msg = JText::sprintf('COM_EASYSTAGING_CLI_FAILED_TO_MERGE_TABLE_X', $step->action);
			}
		}
		else
		{
			$msg = JText::_('COM_EASYSTAGING_CLI_FAILED_TO_GET_TABLE_PROFILES');
			$status = false;
		}

		$this->_log($step, $msg);

		// We need to swap our source and target databases BACK
		$this->swapSourceTarget();

		// Return our status
		return $status;
	}

	/**
	 * Takes the table and it profile and builds up an SQL to perform the merge.
	 *
	 * @param   EasyStagingTableSteps  $step           The current step.
	 *
	 * @param   array                  $tableProfiles  Array of useful details about the table
	 *
	 * @return null
	 */
	private function doMergeRecords($step, $tableProfiles)
	{
		// Assume the world works
		$mergeResult = true;

		// Is our data size greater than connection limits?
		$sourceSize = $tableProfiles['source']['table_size'];

		// Yes I hate magic numbers too but this works for the majority of MySQL hosts we've encountered @todo Magic Number run around, scream and panic
		$magicInsertNumberThatStopsMySQLGoingAway = 256000;

		if ( $sourceSize > $magicInsertNumberThatStopsMySQLGoingAway)
		{
			$exportSteps = intval($sourceSize / $magicInsertNumberThatStopsMySQLGoingAway);
			$exportSteps = ($exportSteps > 1) ? $exportSteps : 2;
		}
		else
		{
			$exportSteps = 1;
		}

		// We need to limit each request to fit (for small table limit will be row count + 1)
		$start = 0;
		$limit = intval($tableProfiles['source']['rowCount'] / $exportSteps) + 1;

		// Setup our basics
		$trgPrefix = $this->target_site->database_table_prefix;
		$srcPrefix = $this->source_site->database_table_prefix;
		$srcDB = $this->source_db;
		$trgDB = $this->target_db;
		$srcTableName = $this->swapTablePrefix($step->action, $trgPrefix, $srcPrefix);
		$trgTableName = $step->action;
		$srcPk = $tableProfiles['source']['pk'];


		// Drop any previous temporary table
		$tempTable = $trgPrefix . 'easystaging_temp';
		$trgDB->dropTable($tempTable, true);

		// Create a temporary table to store the source data in
		$createStatement = $srcDB->getTableCreate($srcTableName);
		$trgQuery = str_replace($srcTableName, $tempTable, $createStatement[$srcTableName]);
		$trgDB->setQuery($trgQuery);

		if ($createTempResult = $trgDB->execute())
		{
			/** @var $query JDatabaseQuery */
			$query = $srcDB->getQuery(true);
			$query->select('*')->from($srcTableName);
			$query->order($srcPk);

			// Loop through our select's and process each block of data
			for ($i = 1; $i <= $exportSteps; $i++)
			{
				$srcDB->setQuery($query, $start, $limit);
				$rows = $srcDB->loadAssocList();
				$rowsRetreived = count($rows);

				// ADD the records to the local temporary table.
				if (!$insertResult = $this->insertRows($rows, $trgDB, $tempTable))
				{
					$mergeResult = false;
					$this->_log($step, JText::sprintf('COM_EASYSTAGING_CLI_MERGE_BACK_FAILED_FOR_X', $srcTableName));
					break;
				}
				else
				{
					$this->_log($step, JText::sprintf('COM_EASYSTAGING_CLI_MERGE_BACK_RETREIVED_X_RECORDS_STARTING_AT_Y_FROM_Z', $rowsRetreived, $start, $srcTableName));
				}

				// Increment starting point for next block of rows
				$start += $limit;
			}

			if ($mergeResult)
			{
				$qTrgTableName = $trgDB->quoteName($trgTableName);
				$qTempTableName = $trgDB->quoteName($tempTable);
				$mergeQuery = "REPLACE INTO $qTrgTableName SELECT * FROM $qTempTableName;";
				$trgDB->setQuery($mergeQuery);

				if (!$trgDB->execute())
				{
					$mergeResult = false;
					$this->_log($step, JText::sprintf('COM_EASYSTAGING_CLI_INSERT_IGNORE_FAILED_FOR_X', $srcTableName));
				}
			}
		}

		// Finally clear out our temp table — best not to leave a mess
		$trgDB->dropTable($tempTable, true);

		return $mergeResult;
	}

	/**
	 * Inserts rows into the table.
	 *
	 * @param   array      $rows   Array of the rows to be inserted.
	 *
	 * @param   JDatabase  $db     The target database.
	 *
	 * @param   string     $table  The table name.
	 *
	 * @return  bool
	 */
	private function insertRows($rows, $db, $table)
	{
		// Get our columns
		$firstRow = $rows[0];
		$columns = array_keys($firstRow);

		// Build our query
		$query = $db->getQuery(true);
		$query->insert($table);
		$query->columns($columns);

		// Assemble our values
		foreach ($rows as $row)
		{
			// Process each row for slashes, new lines.
			foreach ($row as $field => $value)
			{
				$row[$field] = addslashes($value);
				$row[$field] = str_replace("\n", '\n', $row[$field]);
			}

			// Convert our row to a suitable values string
			$rowAsValues  = "'" . implode("', '", $row) . "'";

			$query->values($rowAsValues);
		}

		// Set our query and return the result
		$db->setQuery($query);

		return $db->execute();
	}

	/**
	 * Gather the profile of the table in the step for both the target and source db's.
	 *
	 * @param   EasyStagingTableSteps  $step  This step.
	 *
	 * @return array|bool
	 */
	private function getTableProfile($step)
	{
		// Lets setup
		$profile = array('target' => array(), 'source' => array());
		$srcPrefix = $this->source_site->database_table_prefix;
		$trgPrefix = $this->target_site->database_table_prefix;

		// Start with our source DB first (i.e. the one we're getting our records)
		$db = $this->source_db;
		$tableName = $this->swapTablePrefix($step->action, $trgPrefix, $srcPrefix);
		$query = RunHelper::buildTableProfileQuery($tableName, $this->source_site->database_name, $db);
		$db->setQuery($query);

		if ($tableProfile = $db->loadAssoc())
		{
			$msg = JText::sprintf('COM_EASYSTAGING_CLI_GETTING_TABLE_PROFILE_FOR_X', $tableName);

			// Now we need the primary key for the table...
			$pkQuery = RunHelper::buildTablePkQuery($this->source_site->database_name, $tableName);
			$db->setQuery($pkQuery);
			$pk = $db->loadRow();
			$tableProfile['pk'] = $pk[0];

			// Now the record count.
			$rowcountQuery = sprintf("SELECT COUNT(*) FROM %s", $db->quoteName($tableName));
			$db->setQuery($rowcountQuery);
			$rowCount = $db->loadRow();
			$tableProfile['rowCount'] = $rowCount[0];
			$profile['source'] = $tableProfile;

			// Ok get the target table size details (i.e. the table that will receive the records)
			$db = $this->target_db;
			$tableName = $this->swapTablePrefix($tableName);
			$query = RunHelper::buildTableProfileQuery($tableName, $this->target_site->database_name, $db);
			$db->setQuery($query);
			$tableProfile = $db->loadAssoc();

			// Now the record count.
			$rowcountQuery = sprintf("SELECT COUNT(*) FROM %s", $tableName);
			$db->setQuery($rowcountQuery);
			$rowCount = $db->loadRow();
			$tableProfile['rowCount'] = $rowCount[0];
			$profile['target'] = $tableProfile;
		}
		else
		{
			$msg = JText::sprintf('COM_EASYSTAGING_CLI_FAILED_TO_GET_PROFILE_FOR_X', $tableName);
			$profile = false;
		}

		$this->_log($step, $msg);

		return $profile;
	}

	/**
	 * DATABASE SECTION
	 */

	/**
	 * Check the connection to the target database ...
	 *
	 * @param   EasyStagingTableSteps  $theStep  The current step.
	 *
	 * @return  bool
	 */
	private function checkDBConnection($theStep)
	{
		// Assume failure
		$status = false;

		// Get our plan
		$plan_id = $this->_plan_id();

		// Get the target site details
		$target_site = $this->target_site;
		$options = array(
			'host'		=> $target_site->database_host,
			'user'		=> $target_site->database_user,
			'password'	=> $target_site->database_password,
			'database'	=> $target_site->database_name,
			'prefix'	=> $target_site->database_table_prefix,
		);

		// Get our DB objects, assuming for now that we're dealing with PUSH action, so the source is local & target is remote
		$this->target_db = JDatabase::getInstance($options);
		$this->source_db  = JFactory::getDbo();

		if ($this->target_db->getErrorNum() == 0)
		{
			$q = "SHOW VARIABLES LIKE 'max_allowed_packet'";
			$this->target_db->setQuery($q);
			$qr = $this->target_db->loadRow();

			if ($qr)
			{
				// Use slightly less than actual max to avoid the CSUA doublebyte issue...
				$this->max_ps = (int) ($qr[1] * 0.95);

				// Get our max Kb set by preferences, convert to bytes
				$prefMax = $this->params->get('max_allowed_packet', 2000) * 1024;

				// Is our max_ps > max_allowed_packet set in preference?
				$this->max_ps = ($this->max_ps > $prefMax) ? $prefMax : $this->max_ps;

				$msg = JText::_('COM_EASYSTAGING_DATABASE_STEP_01_CONNECTED');
				$this->_log($theStep, $msg);
				$this->targetTablesRetreived = $this->target_db->getTableList();

				if ($this->targetTablesRetreived)
				{
					$tableList = print_r($this->targetTablesRetreived, true);

					$this->_log($theStep, $tableList);
					$status = true;
				}
			}
		}
		else
		{
			$msg = JText::_('COM_EASYSTAGING_DATABASE_STEP_01_FAILED_TO_CONNECT');
			$this->_log($theStep, $msg);
		}

		return $status;
	}

	/**
	 * Utility method to swap the source and target resources
	 *
	 * @return  null
	 */
	private function swapSourceTarget()
	{
		// Swap the databases
		$ldb = $this->source_db;
		$this->source_db = $this->target_db;
		$this->target_db = $ldb;

		// Swat the sites
		$ls = $this->source_site;
		$this->source_site = $this->target_site;
		$this->target_site = $ls;
	}

	/**
	 * Create our export file for the table in this step.
	 *
	 * @param   EasyStagingTableSteps  $step  The current step.
	 *
	 * @return  bool
	 */
	private function createCopyTable_ExportFile($step)
	{
		// If we can't create the export file we have to abort
		$status = false;
		$table = $step->action;
		$msg = JText::sprintf('COM_EASYSTAGING_CLI_CREATING_SQL_EXPORT_FOR_X', $table) . "\n";

		// Build our file path & file handle
		$pathToSQLFile = $this->_sync_files_path() . $this->_get_run_directory() . '/' . $this->_export_copy_file_name($table);


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

		$targetTableName = $this->target_db->quoteName($this->swapTablePrefix($table));
		$sourceTableName = $this->source_db->quoteName($table);

		// Get any filter that may apply to this table.
		$hasAFilter = $this->_filterTable($table);

		// Build our SQL to recreate the table on the target server.
		// Disable keys and Lock our table before replacing it and then unlock and enable keys after.
		$startSQL = 'LOCK TABLES ' . $targetTableName . " WRITE;\n" .
			"\n\n-- End of Statement --\n\n" .
			'ALTER TABLE ' . $targetTableName . " DISABLE KEYS;\n" .
			"\n\n-- End of Statement --\n\n";

		$buildTableSQL .= $startSQL;

		// 1. First we drop the existing table
		$buildTableSQL .= 'DROP TABLE IF EXISTS ' . $targetTableName . ";\n\n-- End of Statement --\n\n";

		// 2. Then we create it again, except with a new prefix :D
		$this->source_db->setQuery('SHOW CREATE TABLE ' . $sourceTableName);
		$createStatement = $this->source_db->loadRow();
		$createStatement = $this->swapTablePrefix($createStatement);
		$buildTableSQL .= str_replace("\r", "\n", $createStatement[1]) . ";\n\n-- End of Statement --\n\n";

		// 3. Next we try and get the records in the table (after all no point in creating an insert statement if there are no records :D )
		/** @var $dbq JDatabaseQuery */
		$dbq = $this->source_db->getQuery(true);

		// @todo really? all of the records — lets fix this.
		$dbq->select('*');
		$dbq->from($sourceTableName);

		if ($hasAFilter)             // If our table has an exclusion filter we need to add a 'where' element to our query.
		{
			$fieldToCompare = key($hasAFilter);
			$valueToAvoid = $hasAFilter[$fieldToCompare];
			$condition = $this->source_db->quoteName($fieldToCompare) . ' NOT LIKE ' . $this->source_db->quote($valueToAvoid);
			$dbq->where($condition);
		}

		$this->source_db->setQuery($dbq);

		if (($records = $this->source_db->loadRowList()) != null)
		{
			// 4. Then we build the list of field/column names that we'll insert data into
			// -- first we get the columns
			$flds = $this->_getArrayOfFieldNames($table, $this->source_db);

			// No problems getting the field names?
			if ($flds)
			{
				$msg .= JText::sprintf('COM_EASYSTAGING_CLI_CREATING_INSERT_STATEMEN_MSG_X', count($records));
				$this->_log($step, $msg);

				// -- then we implode them into a suitable statement
				$columnInsertSQL = 'INSERT INTO ' . $targetTableName . ' (' . implode(', ', $flds) . ') VALUES ';

				// - keeping it intact for later user if the table is too big.

				// 5. Now we can process the rows into INSERT values
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
						$row[$field] = str_replace("\n", "\\n", $row[$field]);
					}

					// Convert our row to a suitable values string
					$rowAsValues  = "('" . implode("', '", $row) . "')";

					// First up we check to see if this row will put our SQL block size over our max_packet value on the target server
					$rowSize = strlen($rowAsValues);

					// Have we reached our block size? If so, add the current data to the build SQL and reset for next block of SQL.
					if ($this->max_ps < ($sizeOfSQLBlock += $rowSize))
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
				$endofSQL = $this->_end_of_export_SQL($table);
				$endofSQL = $this->swapTablePrefix($endofSQL);

				$buildTableSQL .= $endofSQL;

				// 6. Save the export SQL to file for the next request to execute.
				if ($exportSQLFile = @fopen($pathToSQLFile, 'w'))
				{
					// Attempt to write the file
					if ($status = fwrite($exportSQLFile, $buildTableSQL))
					{
						$msg = JText::sprintf('COM_EASYSTAGING_SQL_EXPORT_SUCC', $table) . "\n";
						$status = true;
					}
					else
					{
						$msg = JText::sprintf('COM_EASYSTAGING_CLI_UNABLE_TO_WRITE_SQL_EXPORT_FOR_TABLE_X', $table);
					}

					$msg .= JText::sprintf('COM_EASYSTAGING_CLI_PATH_TO_SQL_EXPORT_FILE_X_FOR_TABLE_Y', $pathToSQLFile, $table);

					// Time to close off
					fclose($exportSQLFile);
				}
				else
				{
					$msg = JText::_('COM_EASYSTAGING_CLI_FAILED_TO_OPEN_SQL_EXP_FILE');
					$msg .= error_get_last();
				}
			}
			else
			{
				/**
				 * Ahh... bugger, Joomla! found a column name it didn't like (i.e. a column name that the current db doesn't like)
				 * Typical causes are columns names that start with a number or other illegal character or are completely numeric
				 *
				 */
				$msg = JText::_('COM_EASYSTAGING_TABLE_CONTAINS_INVALID_COLS_NAMES');
			}
		}
		else
		{
			$msg = JText::sprintf('COM_EASYSTAGING_CLI_TABLE_X_IS_EMPTY_NO_INS_REQ', $table);
			$endofSQL = $this->_end_of_export_SQL($table);
			$endofSQL = $this->swapTablePrefix($endofSQL);
			$buildTableSQL .= $endofSQL;

			if ($exportSQLFile = @fopen($pathToSQLFile, 'w'))
			{
				// Attempt to write the file
				if ($status = fwrite($exportSQLFile, $buildTableSQL))
				{
					$msg .= JText::sprintf('COM_EASYSTAGING_SQL_EXPORT_SUCC', $table) . "\n";
					$status = true;
				}
				else
				{
					$msg .= JText::sprintf('COM_EASYSTAGING_CLI_UNABLE_TO_WRITE_SQL_EXPORT_FOR_TABLE_X', $table);
				}

				$msg .= JText::sprintf('COM_EASYSTAGING_CLI_PATH_TO_SQL_EXPORT_FILE_X_FOR_TABLE_Y', $pathToSQLFile, $table);

				// Time to close off
				fclose($exportSQLFile);
			}
		}

		// Log it...
		$this->_log($step, $msg);

		return $status;
	}

	/**
	 * Simple function for end of export SQL
	 *
	 * @param   string  $table  The name of the table
	 *
	 * @return  string
	 */
	private function _end_of_export_SQL($table)
	{
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

		return $endofSQL;
	}

	/**
	 * Empties the target table. (We don't use truncate as we don't want the index's to reset.
	 *
	 * @param   EasyStagingTableSteps  $step  The step.
	 *
	 * @return  int|mixed
	 */
	private function emptyTable($step)
	{
		$table = $step->action;
		$targetTableName = $this->target_db->quoteName($this->swapTablePrefix($table));

		/** @var $query JDatabaseQuery */
		$query = $this->target_db->getQuery(true);
		$query->delete($targetTableName);

		$this->target_db->setQuery($query);

		return $this->target_db->execute();
	}

	/**
	 * Runs the exported tables SQL file.
	 *
	 * @param   EasystagingTableSteps  $step  The step.
	 *
	 * @return  bool
	 */
	private function runTableCopyExport($step)
	{
		$last_msg = '';
		$tableName = $step->action;
		$finishing = false;
		$pathToSQLFile = $this->_sync_files_path() . $this->_get_run_directory() . '/' . $this->_export_copy_file_name($tableName);

		// Make sure our file exists
		if (($pathToSQLFile != '') && (file_exists($pathToSQLFile)))
		{
			$msg = JText::sprintf('COM_EASYSTAGING_CLI_FOUND_SQL_EXPOR_FILE', $tableName) . "\n";

			$exportSQLQuery = explode("\n\n-- End of Statement --\n\n", file_get_contents($pathToSQLFile));


			// Update progress
			$msg .= JText::sprintf('COM_EASYSTAGING_CLI_EXPORT_FILE_X_HAS_Y_STATEMENTS', $tableName, count($exportSQLQuery));
			$this->_log($step, $msg);

			// New Tablename
			$newTableName = $this->swapTablePrefix($tableName);

			if (count($exportSQLQuery))
			{
				$target_site = $this->target_site;

				if ($this->target_db)
				{
					$last_word = '';

					// Run queries from the SQL file.
					foreach ($exportSQLQuery as $query)
					{
						if (!empty($query))
						{
							list($first_word) = explode(' ', trim($query));
							$this->target_db->setQuery($query);

							if ($this->target_db->query())
							{
								if (($first_word == 'SET' && $last_word == 'UNLOCK') || ($first_word == 'SET' && $finishing))
								{
									$first_word = 'UNSET';
									$finishing = true;
								}

								if (($first_word == 'SET' && $last_word != 'SET')
									|| ($first_word == 'UNSET' && $last_word != 'UNSET')
									|| ($first_word != 'SET' && $first_word != 'UNSET'))
								{
									$msg = JText::sprintf('COM_EASYSTAGING_CLI_TABLE_EXPORT_QUERY_' . strtoupper($first_word), $newTableName, $target_site->database_name);
								}

								$last_word = $first_word;
							}
							else
							{
								$msg = JText::sprintf(
									'COM_EASYSTAGING_CLI_TABLE_FAILED_EXPORT_QUERY_' . strtoupper($first_word),
									$newTableName, $this->target_db->getErrorMsg()
								);
							}
						}

						// Update progress
						if ($msg != $last_msg)
						{
							$this->_log($step, $msg);
							$last_msg = $msg;
						}
					}
				}

				/**
				 * @todo Confirm result, how? Check a matching number of records? What else? Maybe check the create statement?.
				 */
			}
			else
			{
				$msg = JText::sprintf('COM_EASYSTAGING_JSON_FAILED_TO_READ_SQL_FILE', $tableName, $pathToSQLFile);
			}
		}
		else
		{
			$msg = JText::sprintf('COM_EASYSTAGING_JSON_COULDNT_FIND_SQL_FILE', $tableName, $pathToSQLFile);
		}

		// Update progress
		if ($msg != $last_msg)
		{
			$this->_log($step, $msg);
		}

		return $finishing;
	}

	/**
	 * Strips out just the field names from the assoc array provided by Joomla!
	 *
	 * @param   string     $table  The name of the table.
	 *
	 * @param   JDatabase  $db     The database to use.
	 *
	 * @return  array|bool         On success a single column list of field names, false on failure.
	 */
	private function _getArrayOfFieldNames($table, $db)
	{
		$tableFields = $db->getTableColumns($table);
		$fieldNames = array();

		foreach ($tableFields as $aField => $aFieldType)
		{
			if (!is_numeric($aField) && ($thisFldName = $db->quoteName($aField)) && is_string($thisFldName))
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

	/**
	 * Looks for table name in our hard-coded filters array.
	 *
	 * @param   string  $tablename  The table we're checking for a filter
	 *
	 * @return  array|bool  A filter if one exists | false if not.
	 */
	private function _filterTable($tablename)
	{
		// We don't want to remove the underscore
		$sourcePrefix = $this->source_db->getPrefix();
		$filters = array(
			$sourcePrefix . 'assets'		=> array('name' => 'com_easystaging%'),
			$sourcePrefix . 'extensions'	=> array('element' => 'com_easystaging'),
			$sourcePrefix . 'menu'		=> array('alias' => 'easystaging'),
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

	/**
	 * Replaces source prefix with target prefix
	 *
	 * @param   string  $sql          The SQL to be changed.
	 *
	 * @param   string  $orig_prefix  The prefix used in the supplied SQL, if empty defaults to sourcePrefix

	 * @param   string  $new_prefix   The replacement prefix for the supplied SQL, if empty defaults to targetPrefix

	 * @return  mixed
	 */
	private function swapTablePrefix($sql, $orig_prefix = '', $new_prefix = '')
	{
		$orig_prefix = $orig_prefix ? $orig_prefix : $this->source_db->getPrefix();
		$new_prefix  = $new_prefix ? $new_prefix : $this->target_db->getPrefix();

		if ($orig_prefix != $new_prefix)
		{
			$sql = str_replace($orig_prefix, $new_prefix, $sql);
		}

		return $sql;
	}

	/**
	 * PLAN RUN UTILITIES SECTION
	 */

	/**
	 * Basic utility for logging to both the user and log file.
	 *
	 * @param   EasyStagingTableSteps  $step  The current step.
	 *
	 * @param   string                 $msg   The log message.
	 *
	 * @return  null
	 */
	private function _log($step, $msg)
	{
		RunHelper::updateResults($step, RunHelper::addBRsToLineEnds($msg));
		$this->_writeToLog($msg);
	}

	/**
	 * Central point for writing out details to our run log.
	 *
	 * @param   string  $logLine  The line to record in the run log.
	 *
	 * @return  bool|int
	 */
	private function _writeToLog($logLine)
	{
		$logWriteResult = false;
		$runDirectory = RunHelper::get_run_directory($this->runticket);

		if (!is_array($runDirectory))
		{
			if (!isset($this->logFile) || ($this->logFile === false))
			{
				$logFileName = 'es-log-plan-run-' . $this->runticket . '.txt';
				$fullPathToLogFile = JPATH_COMPONENT_ADMINISTRATOR . '/syncfiles/' . $this->runticket . '/' . $logFileName;

				$this->logFile = fopen($fullPathToLogFile, 'ab');
			}

			if ($this->logFile)
			{
				// 'ab' has 'b' for windows :D
				$logWriteResult = fwrite($this->logFile, $logLine . "\n");
			}
		}

		return $logWriteResult;
	}

	/**
	 * The cental point our sync files path is defined, later this will use a preference setting.
	 *
	 * @return string
	 */
	private function _sync_files_path()
	{
		return JPATH_COMPONENT_ADMINISTRATOR . '/syncfiles/';
	}

	private function _run_files_path()
	{
		return JPATH_COMPONENT_ADMINISTRATOR . '/syncfiles/' . $this->_get_run_directory();
	}
	/**
	 * Returns the run directory name in the nominated syncfile directory, creating one if it doesn't already exist.
	 *
	 * @param   string  $runDirectory  The runticket.
	 *
	 * @return  array|string  String i.e. directory path if all is good, other array with error results.
	 */
	private function _get_run_directory($runDirectory = null)
	{
		// Get location files from this run will be saved in to.
		if ($runDirectory == null)
		{
			$runDirectory = $this->runticket;
		}

		$runDirectoryPath = JPATH_COMPONENT_ADMINISTRATOR . '/syncfiles/' . $runDirectory;

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
			$result['msg'] = JText::_('COM_EASYSTAGING_PLAN_JSON_NO_VALID_RUN_TICKET_1');
		}

		return $result;
	}

	/**
	 * Central pont for defining the name of the exclusions file for the rsync call, later this will use a preference setting.
	 *
	 * @param   int  $rsyncId  ID of the File Copy Action.
	 *
	 * @return string
	 */
	private function _excl_file_name($rsyncId)
	{
		return ('plan-' . $this->_plan_id() . '-' . $rsyncId . '-exclusions.txt');
	}

	/**
	 * Central point for creating the SQL copy exports file name for a table.
	 *
	 * @param   string  $table  The name of the table.
	 *
	 * @return string
	 */
	private function _export_copy_file_name($table)
	{
		return ('plan-' . $this->_plan_id() . '-' . $table . '-export.sql');
	}

	/**
	 * Central point for creating the rsync streaming out file name, later this will use a preference setting.
	 *
	 * @return string
	 */
	private function _rsync_output_file_name()
	{
		return ('plan-' . $this->runticket . '-rsyncout.txt');
	}

	/**
	 * Returns the plan id, extracting it if necessary from the current run ticket.
	 *
	 * @return  int
	 *
	 * @throws Exception
	 */
	private function _plan_id()
	{
		if (!isset($this->plan_id) || is_null($this->plan_id))
		{
			if (RunHelper::runTicketIsValid($this->runticket))
			{
				$runticket_details = RunHelper::getTicketDetails($this->runticket);

				if (!is_null($runticket_details))
				{
					$this->plan_id = $runticket_details['plan_id'];
				}
				else
				{
					throw new Exception('Unable to determine plan id from run ticket.');
				}
			}
		}

		return $this->plan_id;
	}


	/**
	 * GENERAL UTILITIES SECTION
	 */

	/**
	 * Parses POSIX command line options and returns them as an associative array. Each array item contains
	 * a single dimensional array of values. Arguments without a dash are silently ignored.
	 *
	 * @return array
	 */
	private function parseOptions()
	{
		global $argc, $argv;

		// Workaround for PHP-CGI
		if (!isset($argc) && !isset($argv))
		{
			$query = "";

			if (!empty($_GET))
			{
				foreach ($_GET as $k => $v)
				{
					$query .= " $k";

					if ($v != "")
					{
						$query .= "=$v";
					}
				}
			}

			$query = ltrim($query);
			$argv = explode(' ', $query);
			$argc = count($argv);
		}

		$currentName	= "";
		$options		= array();

		for ($i = 1; $i < $argc; $i++)
		{
			$argument = $argv[$i];

			if (strpos($argument, "-") === 0)
			{
				$argument = ltrim($argument, '-');

				if (strstr($argument, '='))
				{
					list($name, $value) = explode('=', $argument, 2);
				}
				else
				{
					$name = $argument;
					$value = null;
				}

				$currentName = $name;

				if (!isset($options[$currentName]) || ($options[$currentName] == null))
				{
					$options[$currentName] = array();
				}
			}
			else
			{
				$value = $argument;
			}

			if ((!is_null($value)) && (!is_null($currentName)))
			{
				if (strstr($value, '='))
				{
					$parts = explode('=', $value, 2);
					$key = $parts[0];
					$value = $parts[1];
				}
				else
				{
					$key = null;
				}

				$values = $options[$currentName];

				if (is_null($key))
				{
					array_push($values, $value);
				}
				else
				{
					$values[$key] = $value;
				}

				$options[$currentName] = $values;
			}
		}

		return $options;
	}

	/**
	 * Returns the value of a command line option
	 *
	 * @param   string  $key              The full name of the option, e.g. "foobar"
	 *
	 * @param   mixed   $default          The default value to return
	 *
	 * @param   bool    $first_item_only  Return only the first value specified (default = true)
	 *
	 * @return mixed
	 */
	private function getOption($key, $default = null, $first_item_only = true)
	{
		static $options = null;

		if (is_null($options))
		{
			$options = $this->parseOptions();
		}

		if ( !array_key_exists($key, $options) )
		{
			return $default;
		}
		else
		{
			if ( $first_item_only )
			{
				return $options[$key][0];
			}
			else
			{
				return $options[$key];
			}
		}
	}

	/**
	 * Close off the run, update the last run timestamp.
	 *
	 * @param   int  $plan_id  The plan that's finishing.
	 *
	 * @return  null
	 */
	private function finishRun($plan_id)
	{
		$exitMsg = JText::_('COM_EASYSTAGING_CLI_EXITED_NORMALLY');
		$this->_log($this->rootStep, $exitMsg);
		RunHelper::markCompleted($this->rootStep, '');

		/** @var $closingMsgStep EasyStagingTableSteps */
		$closingMsgStep = JTable::getInstance('Steps', 'EasyStagingTable');
		$closingMsgStep->bind(array('action' => 99,));

		// Get the plan
		/** @var $thePlan EasyStagingTablePlan */
		$thePlan = JTable::getInstance('Plan', 'EasyStagingTable');
		$thePlan->load($plan_id);

		// Update the last run
		$last_run = new JDate('now');

		// Assemble the updates, bind and store them
		$updates = array('last_run' => $last_run->toSql(), 'published' => 1);
		$thePlan->bind($updates);
		$thePlan->store();

		// Time to tidy up...
		if (isset($this->logFile) && ($this->logFile !== false))
		{
			fclose($this->logFile);
		}

		unset($this->logFile);
	}
}

JApplicationCli::getInstance('EasyStaging_PlanRunner')->execute();
