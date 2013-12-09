<?php

// No direct access
defined('_JEXEC') or die;

if (file_exists(JPATH_COMPONENT_ADMINISTRATOR . '/helpers/plan.php'))
{
	require_once JPATH_COMPONENT_ADMINISTRATOR . '/helpers/plan.php';
}
else
{
	die("EasyStaging isn't installed correctly.");
}


/**
 * EasyStaging component helper.
 *
 * @package  EasyStaging
 *
 * @since    1.1.0
 *
 */
class RunHelper
{
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

	public static $extension = 'com_easystaging';

	/**
	 * Checks that the ticket is of a valid format, the plan is published (for the runner the UUID is irrelevant).
	 *
	 * @param   string  $runticket  The run ticket string. PlanId-DTS-UUID
	 *
	 * @return  boolean
	 */
	public static function runTicketIsValid($runticket)
	{
		// Assume bad people are out to get us.
		$result = false;

		// Check our run ticket structure
		$rtarray = self::getTicketDetails($runticket);

		if (count($rtarray) == 3)
		{
			// Get the plan
			if (isset($rtarray['plan_id']))
			{
				/** @var $thePlan EasyStagingTablePlan */
				$thePlan = PlanHelper::getPlan($rtarray['plan_id']);

				// Does the plan exist and is it published or running? (Both are OK)
				if ($thePlan && (($thePlan->published == 1) || ($thePlan->published == 2)))
				{
					// Is the uuid the right length? (it's the only thing we can check...)
					if ((strlen($rtarray['rt_uuid']) == 13) || (strlen($rtarray['rt_uuid']) == 23))
					{
						$result = true;
					}
				}
			}
		}

		return $result;
	}

	/**
	 * Returns keyed array of run ticket elements.
	 *
	 * @param   string  $runticket  The runticket (normally a hyphen segmented string).
	 *
	 * @return array
	 */
	public static function getTicketDetails($runticket)
	{
		$runticket_details = array();
		$rtarray = explode('-', $runticket);

		// Make sure we got something potentially useful back from the explode (and not just the orginal value)
		if ($rtarray && ($rtarray[0] != $runticket))
		{
			$runticket_details['plan_id'] = isset($rtarray[0]) ? $rtarray[0] : '';
			$runticket_details['dts']     = isset($rtarray[1]) ? $rtarray[1] : '';
			$runticket_details['rt_uuid'] = isset($rtarray[2]) ? $rtarray[2] : '';
		}

		return $runticket_details;
	}

	/**
	 * By default add BR tags to the end of each line of text (or if an array, the end of each element).
	 *
	 * @param   string|array  $text     The string or array.
	 *
	 * @param   string        $lineEnd  The suffix/line ending to be attached. Defaults to a <br /> tag.
	 *
	 * @return  array|string
	 */
	public static function addBRsToLineEnds($text, $lineEnd = '<br>')
	{
		if (is_string($text))
		{
			// Convert any double \n\n to single \n
			$text = implode('\n', explode('\n\n', $text));

			// Convert any existing <br>\n to \n
			$text = implode('\n', explode('<br>\n', $text));

			$newLines = substr_count($text, "\n");

			if ($newLines)
			{
				$text = implode($lineEnd . "\n", explode("\n", $text));
			}
			else
			{
				if (!self::endsWith($text, $lineEnd))
				{
					$text .= $lineEnd;
				}
			}
		}
		elseif(is_array($text))
		{
			$text = explode("\n", implode($lineEnd . "\n", $text));
		}

		return $text;
	}


	/**
	 * Checks if the string ends with $needle
	 *
	 * @param   string  $haystack  String to search
	 * @param   string  $needle    Search string
	 *
	 * @return bool
	 */
	public static function endsWith($haystack, $needle)
	{
		$length = strlen($needle);

		if ($length == 0)
		{
			return true;
		}

		return (substr($haystack, -$length) === $needle);
	}

	/**
	 * Selects steps for the current run ticket that aren't completed
	 *
	 * @param   string  $runticket  The run ticket.
	 *
	 * @return  mixed
	 *
	 * @since   1.1
	 */
	public static function getRunSteps($runticket)
	{
		$db = JFactory::getDBO();

		// Load the steps for the supplied Run Ticket...
		$query = $db->getQuery(true);
		$query->select('*')->from($db->quoteName('#__easystaging_steps'));
		$query->where($db->quoteName('runticket') . ' = ' . $db->quote($runticket));
		$query->where($db->quoteName('completed') . ' IS NULL');
		$db->setQuery($query);

		$result = $db->loadAssocList();

		return $result;
	}

	/**
	 * Get a step object.
	 *
	 * @param   int  $id  Step record id.
	 *
	 * @return bool
	 */
	public static function getStep($id)
	{
		/** @var $stepTable JTable */
		$stepTable = JTable::getInstance('Steps', 'EasyStagingTable');

		$stepTable->load($id);

		return $stepTable;
	}

	/**
	 * Utilitiy function to update the steps results text i.e. the current status
	 *
	 * @param   EasyStagingTableSteps  $theStep  The step to update.
	 *
	 * @param   string                 $msg      The latest result msg.
	 *
	 * @return  null
	 */
	public static function updateResults($theStep, $msg)
	{
		$theStep->refresh();
		$existing_rt = $theStep->result_text;

		// If this existing result text has already been reported then we can overwrite.
		if ($theStep->reported == self::REPORTED)
		{
			$new_result_text = $msg;
		}
		else
		{
			$existing_rt .= $existing_rt ? "\n" : '';
			$new_result_text = $existing_rt . $msg;
		}

		// Update our step
		self::updateStep($theStep, array('result_text' => $new_result_text, 'state' => self::PROCESSING));
	}

	/**
	 * Marks a step as completed and updates result is if an optional msg is provided.
	 *
	 * @param   JTable  $theStep  The step object.
	 *
	 * @param   string  $msg      The optional message to provide.
	 *
	 * @return null
	 */
	public static function markCompleted($theStep, $msg = '')
	{
		if ($msg != '')
		{
			self::updateResults($theStep, $msg);
		}

		// Completed now
		$completed = new JDate('now');
		$updateArray['completed'] = $completed->toSql();
		$updateArray['state'] = self::FINISHED;

		self::updateStep($theStep, $updateArray);
	}

	/**
	 * Updates the step record by default setting the reported flag to false.
	 *
	 * @param   EasyStagingTableSteps  $theStep      The step being updated.
	 * @param   array                  $updateArray  The values to bind to the step.
	 *
	 * @return null
	 */
	public static function updateStep($theStep, $updateArray)
	{
		if (!isset($updateArray['reported']))
		{
			$updateArray['reported'] = self::NOTREPORTED;
		}

		// Update the step record.
		$theStep->refresh();
		$theStep->bind($updateArray);
		$theStep->store();
	}

	/**
	 * Rsync Methods
	 */
	/**
	 * Builds the appropriate file of exclusion, inserting our defaults along the way.
	 *
	 * @param   object                 $thisRun  The current run object or a reasonable facsimilie
	 *
	 * @param   EasyStagingTableSteps  $theStep  The current step or a reasonable facsimilie.
	 *
	 * @return array
	 */
	public static function createRSYNCExclusionFile($thisRun, $theStep)
	{
		$decoded_details = json_decode($theStep->action);

		// Build our file path & file handle
		$pathToExclusionsFile = self::get_run_directory($thisRun->runticket) . '/' . self::excl_file_name($decoded_details->id, $thisRun->runticket);
		$result = array(
			'fileName' => $pathToExclusionsFile,
		);
		$result['fullPathToExclusionFile'] = self::sync_files_path() . $pathToExclusionsFile;

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
- /media/com_easystaging
- /tmp/
- /.htaccess
- .DS_Store

DEF;

			// Combine the default exclusions with those in the local site record
			$allExclusions = $defaultExclusions . trim(self::checkExclusionField($decoded_details->file_exclusions));
			$result['fileData'] = $allExclusions;

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
	public static function checkExclusionField($file_exclusions)
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
	 * Checks the passed in option and adds --dry-run based on $dry_run flag
	 *
	 * @param   string  $rsync_options  The passed in options
	 *
	 * @param   bool    $dry_run        Dry run flag, if true we add --dry-run if not present already
	 *
	 * @return  string
	 */
	public static function checkRsyncOptions ($rsync_options, $dry_run = false)
	{
		if ($dry_run && strpos($rsync_options, '--dry-run') === false && strpos($rsync_options, ' -n') === false)
		{
			$rsync_options .= ' --dry-run';
		}

		return $rsync_options;
	}

	/**
	 * Tools for working with directories, files and zip.
	 */

	/**
	 * The cental point our sync files path is defined, later this will use a preference setting.
	 *
	 * @return string
	 */
	public static function sync_files_path()
	{
		return JPATH_COMPONENT_ADMINISTRATOR . '/syncfiles/';
	}

	/**
	 * Returns the run directory name in the nominated syncfile directory, creating one if it doesn't already exist.
	 *
	 * @param   string  $runTicket  The runticket.
	 *
	 * @return  array|string  String i.e. directory path if all is good, other array with error results.
	 */
	public static function get_run_directory($runTicket)
	{
		if ($runTicket)
		{
			// Get location files from this run will be saved in to.
			$runDirectoryPath = JPATH_COMPONENT_ADMINISTRATOR . '/syncfiles/' . $runTicket;

			if (!file_exists($runDirectoryPath))
			{
				if (mkdir($runDirectoryPath, 0777, true))
				{
					return $runTicket;
				}
				else
				{
					$result['status'] = 0;
					$result['msg'] = JText::sprintf('COM_EASYSTAGING_PLAN_JSON_UNABLE_TO_CREAT_RUN_DIR', $runDirectoryPath);
				}
			}
			else
			{
				return $runTicket;
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
	 * @param   object  $thisRun  The current Plan Run object
	 * @param   int     $rsyncId  ID of the File Copy Action.
	 *
	 * @return string
	 */
	private function excl_file_name($thisRun, $rsyncId)
	{
		return ('plan-' . $thisRun->plan_id . '-' . $rsyncId . '-exclusions.txt');
	}


	/**
	 * Create a Zip file
	 *
	 * @param   array   $files        Files to include in zip
	 *
	 * @param   string  $destination  Zip file to put files into.
	 *
	 * @param   string  $stripPath    Base path elements to be stripped from zip's local structure
	 *
	 * @param   bool    $overwrite    Flag to allow overwritting of an existing zip of the same name
	 *
	 * @return  bool
	 */
	public static function createZip($files = array(), $destination = '', $stripPath = '', $overwrite = false)
	{
		// If the zip file already exists and overwrite is false, return false
		if (file_exists($destination) && !$overwrite)
		{
			return false;
		}

		// Vars
		$valid_files = array();

		// If files were passed in...
		if (is_array($files))
		{
			// Cycle through each file
			foreach ($files as $file)
			{
				// Make sure the file exists
				if (file_exists($file))
				{
					$valid_files[] = $file;
				}
			}
		}

		// If we have good files...
		if (count($valid_files))
		{
			// Wouldn't you know it, some servers don't have zip.
			if (class_exists('ZipArchive'))
			{
				// Create the archive
				$zip = new ZipArchive;

				if ($zip->open($destination, $overwrite ? ZIPARCHIVE::OVERWRITE : ZIPARCHIVE::CREATE) !== true)
				{
					return false;
				}

				// Add the files
				foreach ($valid_files as $file)
				{
					$pathFoundInFile = strpos($file, $stripPath);

					if (($pathFoundInFile != false) || ($pathFoundInFile == 0))
					{
						$pathFoundInFile = true;
					}

					if (($stripPath == '') || !$pathFoundInFile)
					{
						$localName = $file;
					}
					else
					{
						$localName = str_replace($stripPath, '', $file);
					}

					$zip->addFile($file, $localName);
				}

				// Debug
				// echo 'The zip archive contains ',$zip->numFiles,' files with a status of ',$zip->status;

				// Close the zip -- done!
				$zip->close();

				// Check to make sure the file exists
				return file_exists($destination);
			}
		}

		return false;
	}

	/**
	 * Convert a directory path to an array of files.
	 *
	 * @param   string  $directory  The path to the directory.
	 *
	 * @param   bool    $recursive  If true, the path is searched recursively within each sub-directory.
	 *
	 * @return array
	 */
	public static function directoryToArray($directory, $recursive = false)
	{
		$array_items = array();

		if ($handle = opendir($directory))
		{
			while (false !== ($file = readdir($handle)))
			{
				if (($file != '.') && ($file != '..') && ($file != 'Thumbs.db') && ($file != '.DS_Store'))
				{
					if (is_dir($directory . '/' . $file))
					{
						if ($recursive)
						{
							$array_items = array_merge($array_items, directoryToArray($directory . '/' . $file, $recursive));
						}

						$file = $directory . '/' . $file;
						$array_items[] = preg_replace('/\/\//si', '/', $file);
					}
					else
					{
						$file = $directory . '/' . $file;
						$array_items[] = preg_replace('/\/\//si', '/', $file);
					}
				}
			}

			closedir($handle);
		}

		return $array_items;
	}

	/**
	 * Deletes the directory at the supplied path.
	 *
	 * @param   string  $directory  The path of the directory to be deleted.
	 *
	 * @return  null
	 */
	public static function remove_this_directory($directory)
	{
		foreach (scandir($directory) as $file)
		{
			if ('.' === $file || '..' === $file)
			{
				continue;
			}

			if (is_dir("$directory/$file"))
			{
				self::remove_this_directory("$directory/$file");
			}
			else
			{
				unlink("$directory/$file");
			}
		}

		rmdir($directory);
	}

	/**
	 * Yuck. In theory every thing will be correctly quoted per the supplied database.
	 *
	 * @param   string          $table   The name of the table
	 * @param   string          $dbName  Name of the database
	 * @param   JDatabaseMySQL  $db      The Joomla DB connection object.
	 *
	 * @return string
	 */
	public static function buildTableProfileQuery ($table, $dbName, $db)
	{
		$tableSizeQueryTemplate = 'SELECT ROUND((%1$s + %2$s), 2) %3$s, %4$s FROM %5$s WHERE %6$s = %7$s AND %8$s = %9$s';
		/**
		 * 1. DATA_LENGTH
		 * 2. INDEX_LENGTH
		 * 3. table_size
		 * 4. AVG_ROW_LENGTH
		 * 5. information_schema.TABLES
		 * 6. table_schema
		 * 7. The target database's name
		 * 8. $table
		 */
		$query = sprintf(
			$tableSizeQueryTemplate,
			$db->quoteName('DATA_LENGTH'),
			$db->quoteName('INDEX_LENGTH'),
			$db->quoteName('table_size'),
			$db->quoteName('AVG_ROW_LENGTH'),
			$db->quoteName('information_schema.TABLES'),
			$db->quoteName('table_schema'),
			$db->quote($dbName),
			$db->quoteName('table_name'),
			$db->quote($table)
		);


		return $query;
	}

	/**
	 * Yuck again. Utility method to build our SQL to retreive the PK of a table.
	 *
	 * @param   string  $dbName     The name of the database.
	 *
	 * @param   string  $tableName  The table name.
	 *
	 * @return string
	 */
	public static function buildTablePkQuery($dbName, $tableName)
	{
		$db = JFactory::getDbo();

		/**
		 *
		 * IT's probably a waste of time doing this as Joomla's DB's support really is MYSQL centric despite
		 * efforts to make multi-db-platform friendly, but, I'm doing it anyway.
		 *
		 * Add value quotes
		 */
		$dbName = $db->quote($dbName);
		$tableName = $db->quote($tableName);
		$pri       = $db->quote('PRI');

		// Quote the column, table and database names
		$info_schema  = $db->quoteName('information_schema');
		$column_name  = $db->quoteName('COLUMN_NAME');
		$pk_label     = $db->quoteName('pk');
		$columns      = $db->quoteName('COLUMNS');
		$table_schema = $db->quoteName('TABLE_SCHEMA');
		$table_name   = $db->quoteName('TABLE_NAME');
		$column_key   = $db->quoteName('COLUMN_KEY');

		// Build the query string
		$tablePKQuery  = "SELECT $column_name $pk_label FROM $info_schema.$columns WHERE ($table_schema = $dbName) ";
		$tablePKQuery .= "AND ($table_name = $tableName) AND ($column_key = $pri)";

		return $tablePKQuery;
	}
}

