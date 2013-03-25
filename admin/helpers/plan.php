<?php

// No direct access
defined('_JEXEC') or die;

/**
 * EasyStaging component helper.
 *
 */
class PlanHelper
{
	public static $extension = 'com_easystaging';
	public static $base_assett = 'plan';
	private static $ext_actions = array('easystaging.run');

	/**
	 * Gets the local site record for the plan.
	 *
	 */
	public static function getLocalSite($plan_id = 0)
	{
		return self::_loadLocalSiteRecord($plan_id);
	}
	
	public static function getRemoteSite($plan_id = 0)
	{
		return self::_loadRemoteSiteRecord($plan_id);
	}

	private static function _loadLocalSiteRecord($plan_id)
	{
		$type = 1; // Local site
		return self::_loadSiteRecord($plan_id, $type);
	}
	private static function _loadRemoteSiteRecord($plan_id)
	{
		$type = 2; // Live/Target site
		return self::_loadSiteRecord($plan_id, $type);
	}
	private static function _loadSiteRecord($plan_id, $type)
	{
		// Load our site record
		JTable::addIncludePath(JPATH_ADMINISTRATOR.'/components/com_easystaging/tables');
		$Sites = JTable::getInstance('Site', 'EasyStagingTable');
	
		if ($Sites->load(array('plan_id'=>$plan_id, 'type'=>$type)))
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
	* @param	int		The Plan ID.
	*
	* @return	JObject
	*/
	public static function getActions($id = 0)
	{
		$user	= JFactory::getUser();
		$result	= new JObject;
	
		if (empty($id))
		{
			$assetName = self::$extension;
		}
		else {
			$assetName = self::$extension . '.' . self::$base_assett . '.' . (int) $id;
		}
	
		$actions = array_merge( array( 'core.admin', 'core.manage', 'core.create', 'core.edit', 'core.edit.own', 'core.edit.state', 'core.delete' ),
								self::$ext_actions );
	
		foreach ($actions as $action)
		{
			$result->set($action,	$user->authorise($action, $assetName));
		}
	
		return $result;
	}

	public  static function loadJSLanguageKeys($jsFile) {
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
	
	/*
	 * Tools for zipping directories & files.
	 */
	public static function createZip($files = array(), $destination = '', $stripPath = '', $overwrite = false)
	{
		//if the zip file already exists and overwrite is false, return false
		if (file_exists($destination) && !$overwrite)
		{
			return false;
		}
		//vars
		$valid_files = array();
		//if files were passed in...
		if (is_array($files))
		{
			//cycle through each file
			foreach ($files as $file)
			{
				//make sure the file exists
				if (file_exists($file))
				{
					$valid_files[] = $file;
				}
			}
		}
		//if we have good files...
		if (count($valid_files))
		{
			// Wouldn't you know it, some servers don't have zip.
			if (class_exists('ZipArchive'))
			{
				//create the archive
				$zip = new ZipArchive();
				if ($zip->open($destination,$overwrite ? ZIPARCHIVE::OVERWRITE : ZIPARCHIVE::CREATE) !== true)
				{
					return false;
				}
				//add the files
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
				//debug
				//echo 'The zip archive contains ',$zip->numFiles,' files with a status of ',$zip->status;
			  
				//close the zip -- done!
				$zip->close();
			  
				//check to make sure the file exists
				return file_exists($destination);
			}
		}

		return false;
	}
	
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
							$array_items = array_merge($array_items, directoryToArray($directory. '/' . $file, $recursive));
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

	public static function remove_this_directory($directory, $recursive = false) {
		foreach (scandir($directory) as $file)
		{
			if ('.' === $file || '..' === $file)
			{
				continue;
			}
			if (is_dir("$directory/$file"))
			{
				remove_this_directory("$directory/$file");
			}
			else
			{
				unlink("$directory/$file");
			}
		}
		rmdir($directory);
	}
}

