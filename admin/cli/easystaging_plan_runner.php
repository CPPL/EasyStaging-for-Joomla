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
if (array_key_exists('REQUEST_METHOD', $_SERVER)) die();

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
define('JPATH_COMPONENT_ADMINISTRATOR', JPATH_ADMINISTRATOR.'/components/com_easystaging');


/**
 * This script will load the specified plan steps that remaining and execute them.
 *
 * @package  Joomla.CLI
 * @since    2.5
 */
class EasyStaging_PlanRunner extends JApplicationCli
{
	/**
	 * Entry point for the script
	 *
	 * @return  void
	 *
	 * @since   2.5
	 */
	public function doExecute()
	{
		// Purge all old records
		$db = JFactory::getDBO();

		jimport('joomla.application.component.helper');
		$component = JComponentHelper::getComponent('com_easystaging');

		$params = $component->params;

		// Ok, we're under way...
		$this->out('Plan Runner loaded...');

		$runTicket = $this->input->getCmd('runticket', '', 'string');

		// Lets load any steps for the nominated plan run
		if ($runTicket == '')
		{
			$this->out('No run ticket provided.');
		}
		else
		{
			// Our work in here...
			$this->out(sprintf('Run tickek found, id: %s', $runTicket));

			// Find any overrides that may have been passed in...
			$overrides = $this->getOption('override', array(), false);

		}


		// And now we're finished
		$this->out('Exiting plan runner.');
	}
}

JApplicationCli::getInstance('EasyStaging_PlanRunner')->execute();
