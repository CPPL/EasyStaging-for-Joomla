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
		sleep(5);
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
			$this->out(sprintf('Plan Runner Launched, PID: %s', getmypid()));
			$this->out(sprintf('Run tickek found, id: %s', $runTicket));

			// Find any overrides that may have been passed in...
			$overrides = $this->getOption('override', array(), false);

		}


		// And now we're finished
		$this->out('Exiting plan runner.');
	}
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
}

JApplicationCli::getInstance('EasyStaging_PlanRunner')->execute();
