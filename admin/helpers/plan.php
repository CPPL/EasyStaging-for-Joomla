<?php

// No direct access
defined('_JEXEC') or die;

/**
 * EasyStaging component helper.
 *
 * @package  EasyStaging
 *
 * @since    1.0.0
 *
 */
class PlanHelper
{
	public static $extension = 'com_easystaging';

	public static $base_assett = 'plan';

	private static $_ext_actions = array('easystaging.run');

	/**
	 * Loads the specified plan as a JTable object.
	 *
	 * @param   int  $plan_id  The plan in question.
	 *
	 * @return  bool|JTable
	 */
	public static function getPlan($plan_id)
	{
		JTable::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_easystaging/tables');
		$Plan = JTable::getInstance('Plan', 'EasyStagingTable');

		if ($Plan->load($plan_id))
		{
			return $Plan;
		}
		else
		{
			return false;
		}
	}

	/**
	 * Prefix method to Get the local site record for the plan.
	 *
	 * @param   int  $plan_id  Plan identity
	 *
	 * @return  EasyStagingTableSite|bool
	 */
	public static function getLocalSite($plan_id = 0)
	{
		return self::_loadSiteRecord($plan_id, 1);
	}

	/**
	 * Prefix method to Get the remote site record for the plan.
	 *
	 * @param   int  $plan_id  Plan identity
	 *
	 * @return  EasyStagingTableSite|bool
	 */
	public static function getRemoteSite($plan_id = 0)
	{
		return self::_loadSiteRecord($plan_id, 2);
	}

	/**
	 * Gets a site record based on the plan_id and the site type
	 *
	 * @param   int  $plan_id  Plan identity
	 *
	 * @param   int  $type     Local (1) or Remote (2) site.
	 *
	 * @return  EasyStagingTableSite|bool
	 */
	private static function _loadSiteRecord($plan_id, $type)
	{
		// Load our site record
		JTable::addIncludePath(JPATH_ADMINISTRATOR . '/components/com_easystaging/tables');
		$Sites = JTable::getInstance('Site', 'EasyStagingTable');

		if ($Sites->load(array('plan_id' => $plan_id, 'type' => $type)))
		{
			return $Sites;
		}
		else
		{
			return false;
		}
	}

	/**
	* Gets a list of the actions that can be performed.
	*
	* @param   int  $id  The Plan ID.
	*
	* @return  JObject
	*/
	public static function getActions($id = 0)
	{
		$user	= JFactory::getUser();
		$result	= new JObject;

		if (empty($id))
		{
			$assetName = self::$extension;
		}
		else
		{
			$assetName = self::$extension . '.' . self::$base_assett . '.' . (int) $id;
		}

		$actions = array_merge(
			array( 'core.admin', 'core.manage', 'core.create', 'core.edit', 'core.edit.own', 'core.edit.state', 'core.delete' ),
			self::$_ext_actions
		);

		foreach ($actions as $action)
		{
			$result->set($action,	$user->authorise($action, $assetName));
		}

		return $result;
	}

	/**
	 * Parses a javascript file looking for JText keys and then loads them ready for use.
	 *
	 * @param   string  $jsFile  Path to the javascript file.
	 *
	 * @return bool
	 */
	public  static function loadJSLanguageKeys($jsFile)
	{
		if (isset($jsFile))
		{
			$jsFile = JPATH_SITE . $jsFile;
		}
		else
		{
			return false;
		}

		if ($jsContents = file_get_contents($jsFile))
		{
			$languageKeys = array();
			preg_match_all('/Joomla\.JText\._\(\'(.*?)\'\)\)?/', $jsContents, $languageKeys);
			$languageKeys = $languageKeys[1];

			foreach ($languageKeys as $lkey)
			{
				JText::script($lkey);
			}
		}
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

