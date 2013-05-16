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
			$existing_rt .= $existing_rt ? "<br />\n" : '';
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
	 * @param   JTable  $theStep      The step being updated.
	 * @param   array   $updateArray  The values to bind to the step.
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
		$theStep->bind($updateArray);
		$theStep->store();
	}

	/**
	 * Tools for zipping directories & files.
	 */

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
}

