<?php
/**
 * @package    EasyStaging
 * @author     Craig Phillips <craig@craigphillips.biz>
 * @copyright  Copyright (C) 2012 Craig Phillips Pty Ltd.
 * @license    GNU/GPL http://www.gnu.org/copyleft/gpl.html
 * @url        http://www.seepeoplesoftware.com
 */

// No direct access

defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.controller');

/**
 * Class EasyStagingController
 *
 * @package  EasyStaging
 *
 * @since    1.0
 */
class com_EasyStagingInstallerScript
{
	/**
	 * Before anything we run preflight... preflight runs before anything else and while the extracted files
	 * are in the uploaded temp folder.
	 *
	 * @param   string               $type    Is the type of change (install, update or discover_install, not uninstall).
	 *
	 * @param   JInstallerComponent  $parent  Is the class calling this method.
	 *
	 * @return mixed  If preflight returns false, Joomla will abort the update and undo everything already done.
	 */
	public function preflight($type, $parent)
	{
		$preFlightOK = true;

		// Who are we?
		$this->extension = $parent->get('element');
		$this->parent    = $parent;

		// Interesting that we need to load our language here...
		$this->loadLanguage();
		$jversion = new JVersion;

		// Installing component manifest file version
		$relVer = explode(' ', $parent->get("manifest")->version);
		$this->release = $relVer[0];

		// Our minimum PHP Version
		$this->phpmin = $parent->get("manifest")->phpversion;

		// Manifest file minimum Joomla version
		$this->minimum_joomla_release = $parent->get("manifest")->attributes()->version;
		$this->ext_type = $parent->get("manifest")->attributes()->type;
		$this->ext_name = $this->makeName($this->ext_type, $parent->get("manifest")->name);

		if (version_compare(PHP_VERSION, $this->phpmin, '<'))
		{
			Jerror::raiseWarning(null, JText::sprintf('COM_EASYSTAGING_INSTALLER_PHP_VERSION_INCOMPATIBLE_X_Y', PHP_VERSION, $this->phpmin));

			$preFlightOK = false;
		}


		// Abort if the current Joomla release is older
		if (version_compare($jversion->getShortVersion(), $this->minimum_joomla_release, '<') && $preFlightOK)
		{
			Jerror::raiseWarning(null, JText::sprintf('COM_EASYSTAGING_INSTALLER_JOOMLA_VERSION_INCOMPATIBLE_X', $this->minimum_joomla_release));

			$preFlightOK = false;
		}

		// Abort if the component being installed is not newer than||same as the currently installed version
		if ($type == 'update' && $preFlightOK)
		{
			$oldRelease = explode(' ', $this->getParam('version'));
			$rel = $oldRelease[0] . ' to ' . $this->release;

			if (version_compare($this->release, $oldRelease[0], '<'))
			{
				Jerror::raiseWarning(null, JText::sprintf('COM_EASYSTAGING_INSTALLER_NO_DOWNGRADING_X', $rel));

				return false;
			}
		}
		else
		{
			$rel = $this->release;
		}

		echo '<p>' . JText::sprintf('COM_EASYSTAGING_INSTALLER_PREFLIGHT_X_' . strtoupper($type), $rel) . '</p>';

		// Check we have a CLI directory
		$cli_dir = JPATH_ROOT . '/cli';

		if (is_dir($cli_dir))
		{
			if ($preFlightOK)
			{
				$source_file = $parent->getParent()->getPath('source') . '/admin/cli/easystaging_plan_runner.php';

				if (file_exists($source_file))
				{
					if (JFile::move($source_file, $cli_dir . '/easystaging_plan_runner.php'))
					{
						echo '<p>' . JText::_('COM_EASYSTAGING_INSTALLER_PREFLIGHT_' . strtoupper($type) . '_CLI_FILE_MOVED') . '</p>';
					}
					else
					{
						Jerror::raiseWarning(null, JText::_('COM_EASYSTAGING_INSTALLER_PREFLIGHT_' . strtoupper($type) . '_CLI_FILE_MOVE_FAILED'));
						$preFlightOK = false;
					}
				}
				else
				{
					Jerror::raiseWarning(null, JText::_('COM_EASYSTAGING_INSTALLER_PREFLIGHT_' . strtoupper($type) . '_NO_CLI_FILE_FOUND'));
					$preFlightOK = false;
				}
			}
		}
		else
		{
			Jerror::raiseWarning(null, JText::_('COM_EASYSTAGING_INSTALLER_PREFLIGHT_' . strtoupper($type) . '_NO_CLI_DIR_FOUND'));
			$preFlightOK = false;
		}

		if (!$preFlightOK)
		{
			return $preFlightOK;
		}
	}

	/**
	 * Install runs after the database scripts are executed.
	 *
	 * @param   JInstallerComponent  $parent  Is the class calling this method. If the extension is new, the install method is run.
	 *
	 * @return  null|bool  If install returns false, Joomla will abort the install and undo everything already done.
	 */
	public function install($parent)
	{
		echo '<p>' . JText::sprintf('COM_EASYSTAGING_INSTALLER_INSTALLED_VERSION_X', $this->release) . '</p>';

		// You can have the backend jump directly to the newly installed component configuration page
		// $parent->getParent()->setRedirectURL('index.php?option=com_democompupdate');
	}

	/**
	 * Update runs after the database scripts are executed. If the extension exists, then the update method is run.
	 *
	 * @param   JInstallerComponent  $parent  Is the class calling this method.
	 *
	 * @return  bool|null  If this returns false, Joomla will abort the update and undo everything already done.
	 */
	public function update($parent)
	{
		echo '<p>' . JText::sprintf('COM_EASYSTAGING_INSTALLER_UPDATE_TO_VERSION_X', $this->release) . '</p>';

		// You can have the backend jump directly to the newly updated component configuration page
		// $parent->getParent()->setRedirectURL('index.php?option=com_democompupdate');
	}

	/**
	 * Postflight is run after the extension is registered in the database.
	 *
	 * @param   string               $type    Is the type of change (install, update or discover_install, not uninstall).
	 *
	 * @param   JInstallerComponent  $parent  Is the class calling this method.
	 *
	 * @return  null
	 */
	public function postflight($type, $parent)
	{
		/* Always create or modify these parameters
		$params['my_param0'] = 'Component version ' . $this->release;
		$params['my_param1'] = 'Another value';

		/* Define the following parameters only if it is an original install
		if ($type == 'install') {
			$params['my_param2'] = '4';
			$params['my_param3'] = 'Star';
		}

		$this->setParams($params);
		*/

		echo '<p>' . JText::sprintf('COM_EASYSTAGING_INSTALLER_POSTFLIGHT_' . strtoupper($type) . '_VERSION_X', $this->release) . '</p>';
	}

	/**
	 * Uninstall runs before any other action is taken (file removal or database processing).
	 *
	 * @param   JInstallerComponent  $parent  Is the class calling this method
	 *
	 * @return  null
	 */
	public function uninstall($parent)
	{
		echo '<p>' . JText::sprintf('COM_EASYSTAGING_INSTALLER_UNINSTALL_VERSION_X', $this->release) . '</p>';
	}

	/**
	 * Get a variable from the manifest file (actually, from the manifest cache).
	 *
	 * @param   string  $name  Element name
	 *
	 * @return mixed
	 */
	public function getParam($name)
	{
		$db = JFactory::getDbo();
		$query = $db->getQuery(true);
		$query->select($db->quoteName('manifest_cache'))->from($db->quoteName('#__extensions'));
		$query->where($db->quoteName('name') . ' = ' . $db->quote($this->extension));
		$db->setQuery($query);
		$manifest = json_decode($db->loadResult(), true);

		return $manifest[ $name ];
	}

	/**
	 * Sets parameter values in the component's row of the extension table
	 *
	 * @param   array  $param_array  The values
	 *
	 * @return  null
	 */
	private function setParams($param_array)
	{
		if (count($param_array) > 0)
		{
			// Read the existing component value(s)
			$db = JFactory::getDbo();
			$query = $db->getQuery(true);
			$query->select($db->quoteName('params'))->from($db->quoteName('#__extensions'));
			$query->where($db->quoteName('name') . ' = ' . $db->quote($this->extension));
			$db->setQuery($query);

			$params = json_decode($db->loadResult(), true);

			// Add the new variable(s) to the existing one(s)
			foreach ($param_array as $name => $value)
			{
				$params[ (string) $name ] = (string) $value;
			}

			// Store the combined new and existing values back as a JSON string
			$paramsString = json_encode($params);
			$query = $db->getQuery(true);
			$query->update($db->quoteName('#__extensions'))->where($db->quoteName('name') . ' = ' . $db->quote($this->extension));
			$query->set($db->quoteName('params') . ' = ' . $db->quote($paramsString));
			$db->setQuery($query);
			$db->query();
		}
	}

	/**
	 * Loads our language files
	 *
	 * @return null
	 */
	private function loadLanguage()
	{
		$extension = $this->extension;
		$jlang = JFactory::getLanguage();
		$path = $this->parent->getParent()->getPath('source') . '/administrator';
		$jlang->load($extension, $path, 'en-GB', true);
		$jlang->load($extension, $path, $jlang->getDefault(), true);
		$jlang->load($extension, $path, null, true);
		$jlang->load($extension . '.sys', $path, 'en-GB', true);
		$jlang->load($extension . '.sys', $path, $jlang->getDefault(), true);
		$jlang->load($extension . '.sys', $path, null, true);
	}

	/**
	 * A simple method to calculate extensions directory name, note, this only works if the extension name is
	 * the same as interface name, it can handle spaces but nothing else.
	 *
	 * @param   string  $ext_type       A string represent the different installable types.
	 *
	 * @param   string  $manifest_name  The <name> attribute from the install manifest (should be a-z,A-Z)
	 *
	 * @return string
	 */
	private function makeName($ext_type, $manifest_name)
	{
		// Get rid of any spaces
		$manifest_name = str_replace(' ', '_', $manifest_name);
		$name = strtolower($manifest_name);

		return $name;
	}
}
