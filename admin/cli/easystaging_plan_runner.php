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
	 * @var string $runticket
	 */
	protected $runticket;

	/**
	 * @var  int  $plan_id
	 */
	private   $plan_id;

	/**
	 * Action types
	 *
	 * Root action for a run
	 */
	const RUN_ROOT = 0;
	/**
	 * Rsync Actions
	 */
	const RSYNC_PUSH  = 1;
	const RSYNC_PULL  = 2;
	const RSYNC_CLEAR = 3;

	/**
	 * Table Actions
	 */
	const TABLE_DONT_COPY_IGNORE = 10;
	const TABLE_COPY_2_LIVE_ONLY = 11;
	const TABLE_COPY_IF_NOT_FND  = 12;
	const TABLE_MERGE_BACK_COPY  = 13;
	const TABLE_MERGE_BACK_ONLY  = 14;
	const TABLE_MERGE_BACK_CLEAN = 13;

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
	 * Entry point for the script
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
				$steps = RunHelper::getRunSteps($this->runticket);
				$rootStep = null;

				// If we have steps lets process them
				if ($steps)
				{
					// Process each step
					foreach ($steps as $step)
					{
						switch ($step['action_type'])
						{
							case self::RUN_ROOT:
								// Keep the root step for closing off the run
								$rootStep = RunHelper::getStep($step['id']);
								break;

							case self::RSYNC_PUSH:
							case self::RSYNC_PULL:
							case self::RSYNC_CLEAR:
								$this->performRSYNC($step);
								break;

							case self::TABLE_DONT_COPY_IGNORE:
								// Nothing to do here...
								break;

							case self::TABLE_COPY_2_LIVE_ONLY:
							case self::TABLE_COPY_IF_NOT_FND:
								$this->performTableCopy($step);
								break;

							case self::TABLE_MERGE_BACK_COPY:
							case self::TABLE_MERGE_BACK_ONLY:
							case self::TABLE_MERGE_BACK_CLEAN:
								$this->performTableMerge($step);
								break;

							default:
								// Anything else we discard, who knows what crazyness caused this... best to avoid potential damage by doing nothing!
						}
					}

					// Time to mark this as done
					$this->finishRun($rootStep, $this->_plan_id());
				}
				else
				{
					// Else we simply exit.
				}

			}
		}
	}

	/**
	 * The main entry for RSYNC steps.
	 *
	 * @param   array  $step  The step details.
	 *
	 * @since 1.1.0
	 *
	 * @return null
	 */
	private function performRSYNC($step)
	{
		// Let's get our step record so we can update as we go...
		/** @var $theStep JTable */
		$theStep = RunHelper::getStep($step['id']);

		// Create the RSYNC exclusions file
		$rsResult = $this->_createRSYNCExclusionFile($theStep);

		if ($rsResult['status'])
		{
			$msg = JText::sprintf('COM_EASYSTAGING_JSON_RSYNC_STEP_SUCCEEDED', $rsResult['fileName']) . "\n";

			if ($this->_writeToLog($msg))
			{
				RunHelper::updateResults($theStep, $msg);
				$this->runRsync($theStep, $rsResult['fullPathToExclusionFile']);
			}
			else
			{
				RunHelper::markCompleted($theStep, JText::sprintf('COM_EASYSTAGING_JSON_UNABLE_TO_LOG', __FUNCTION__));
			}
		}
		else
		{
			RunHelper::markCompleted($theStep, JText::sprintf('COM_EASYSTAGING_JSON_RSYNC_STEP_FAILED', $this->plan_id));
		}
	}

	/**
	 * Run the rsync
	 *
	 * @param   EasyStagingTableSteps  $theStep   The step.
	 *
	 * @param   string                 $filename  Path to the exclusion file.
	 *
	 * @throws Exception
	 *
	 * @return null;
	 */
	protected function runRsync($theStep, $filename)
	{
		$details = json_decode($theStep->action);

		// First we add the rsync options
		$rsyncCmd = 'rsync ' . $details->rsync_options;

		// Then we add the exclusions file name
		$rsyncCmd .= ' --exclude-from=' . $filename;

		// Add the source
		$rsyncCmd .= ' ' . $details->local_site_path;

		// Add the destination
		$rsyncCmd .= ' ' . $details->remote_site_path;

		// Update the steps results
		RunHelper::updateResults($theStep, JText::sprintf('COM_EASYSTAGING_CLI_RSYNC_CMD_X', $rsyncCmd));

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
				RunHelper::updateResults($theStep, $s);
			}
			$rsyncResult = end($rsyncOutput);
			proc_close($rsync_process);

			// Did it end cleanly?
			if (($rsyncResult != false) && ($rsyncResult == 0))
			{
				// It ended cleanly.
				RunHelper::updateResults($theStep, JText::_('COM_EASYSTAGING_CLI_RSYNC_INDICATES_SUCCESS'));
			}
			else
			{
				RunHelper::updateResults($theStep, JText::_('COM_EASYSTAGING_CLI_RSYNC_INDICATES_FAILURE'));
			}
		}
		else
		{
			RunHelper::updateResults($theStep, JText::_('COM_EASYSTAGING_CLI_RSYNC_PROC_OPEN_FAILED'));
		}

		if (!$this->_writeToLog("\n" . print_r($rsyncOutput, true)))
		{
			throw new Exception(JText::sprintf('COM_EASYSTAGING_JSON_UNABLE_TO_LOG', __FUNCTION__));
		}

		RunHelper::markCompleted($theStep, JText::_('COM_EASYSTAGING_CLI_RSYNC_PROCESS_COMPLETED'));
	}

	private function _createRSYNCExclusionFile($theStep)
	{
		$decoded_details = json_decode($theStep->action);

		// Build our file path & file handle
		$pathToExclusionsFile = $this->_get_run_directory() . '/' . $this->_excl_file_name();
		$result = array(
			'fileName' => $pathToExclusionsFile,
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

			// Combine the default exclusions with those in the local site record
			$allExclusions = $defaultExclusions . trim($this->_checkExclusionField($decoded_details->file_exclusions));
			$result['fileData'] = $allExclusions;
			// Insert <br>'s into exclusions for display in browser
			$allExclusions = implode("<br />\n", explode("\n", $allExclusions));
			RunHelper::updateResults($theStep, $allExclusions);

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
		RunHelper::updateResults($theStep, $result['msg']);
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
	 * The main entry table copy steps.
	 *
	 * @param   array  $details  The table to be copied.
	 *
	 * @return null
	 */
	private function performTableCopy($details)
	{
		// Run the table copy
	}

	/**
	 * The main entry table copy steps.
	 *
	 * @param   array  $details  The table to be copied.
	 *
	 * @return null
	 */
	private function performTableMerge($details)
	{
		// Run the table copy
	}

	/**
	 * PLAN RUN UTILITIES SECTION
	 */

	/**
	 * Central point for writing out details to our run log.
	 *
	 * @param   string  $logLine  The line to record in the run log.
	 *
	 * @return  bool|int
	 */
	private function _writeToLog($logLine)
	{
		$logFileName = 'es-log-plan-run-' . $this->runticket . '.txt';
		$fullPathToLogFile = JPATH_COMPONENT_ADMINISTRATOR . '/syncfiles/' . $this->runticket . '/' . $logFileName;

		if ($logFile = fopen($fullPathToLogFile, 'ab'))
		{
			// 'ab' has 'b' for windows :D
			$logWriteResult = fwrite($logFile, $logLine . "\n");

			return $logWriteResult;
		}

		return false;
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
	 * @return string
	 */
	private function _excl_file_name()
	{
		return ('plan-' . $this->_plan_id() . '-exclusions.txt');
	}

	/**
	 * Central point for creating the SQL exports file name for a table.
	 *
	 * @param   string  $table  The name of the table.
	 *
	 * @return string
	 */
	private function _export_file_name($table)
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


	private function finishRun($rootStep, $plan_id)
	{
		$exitMsg = JText::_('COM_EASYSTAGING_CLI_EXITED_NORMALLY');
		RunHelper::markCompleted($rootStep, $exitMsg);

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
	}
}

JApplicationCli::getInstance('EasyStaging_PlanRunner')->execute();
