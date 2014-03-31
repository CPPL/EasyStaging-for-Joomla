<?php
/* @package		EasyStaging
 * @link		http://seepeoplesoftware.com
 * @license		GNU/GPL
 * @copyright	Craig Phillips Pty Ltd
*/

// No direct access

defined( '_JEXEC' ) or die( 'Restricted access' );

jimport('joomla.application.component.controller');
jimport('joomla.database.table');

/**
 * EasyStaging Component Plan Controller
 */
class EasyStagingControllerPlan extends JController
{
	/**
	 * @var int $plan_id
	 */
	protected $plan_id;

	/**
	 * @var array $params
	 */
	protected $params;

	/**
	 * @var  $logfile
	 */
	protected $logFile;

	/**
	 * Plan is not published i.e. not to be used
	 */
	const UNPUBLISHED  = 0;
	const NOTRUNNING   = 0;
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
	 * Step reported states
	 */
	const NOTREPORTED  = 0;
	const REPORTED     = 1;

	/**
	 * Constructor
	 *
	 * @param   array  $config  Optional configuration params
	 *
	 * @since   1.0
	 */
	public function __construct($config)
	{
		require_once JPATH_COMPONENT . '/helpers/plan.php';
		require_once JPATH_COMPONENT . '/helpers/run.php';

		parent::__construct($config);
		$this->params = JComponentHelper::getParams('com_easystaging');
	}

	/**
	 * Testing functions
	 *
	 * @since 1.1.3
	 *
	 * @return mixed
	 */
	public function checkDBConnection()
	{
		$msg = '';
		$status = 0;
		$result = 0;

		// Check for request forgeries
		if (!JSession::checkToken('request'))
		{
			$msg = JText::_('JINVALID_TOKEN');
		}
		else
		{
			$jIn = JFactory::getApplication()->input;
			$plan_id = $this->_plan_id();

			if ($plan_id)
			{
				// Get our local database connection
				$config = JFactory::getConfig();
				$driver = $config->get('dbtype', 'mysqli');

				// Get the target database details
				$options = array(
					'host'		=> $jIn->get('database_host', ''),
					'user'		=> $jIn->get('database_user', ''),
					'password'	=> $jIn->get('database_password', ''),
					'driver'	=> $driver,
					'port'	    => $this->params->get('port_to_test_remotedb', 3306),
					'database'	=> $jIn->get('database_name', ''),
					'prefix'	=> $jIn->get('database_table_prefix', ''),
				);

				// Check the host is valid
				$host = $options['host'];
				$hostIsIPAddress = filter_var($options['host'], FILTER_VALIDATE_IP);

				if (!$hostIsIPAddress)
				{
					if (($hostIsURL = filter_var($host, FILTER_VALIDATE_URL)) || ($hostIsURL = filter_var('http://' . $host, FILTER_VALIDATE_URL)))
					{
						$hostIsValid = true;
					}
				}
				else
				{
					$hostIsValid = true;
				}

				// Can we contact the host?
				if ($hostIsValid)
				{
					$msg .= JText::_('COM_EASYSTAGING_JSON_TEST_REMOTE_HOSTNAME_OK');
					$port = $this->params->get('port_to_test_remote_host', 80);
					$timeout = $this->params->get('timeout_for_connection_tests', 3);

					$contactEstablished = $this->contactHost($host, $port, $timeout);

					// We have contact
					if ($contactEstablished)
					{
						$msg .= JText::_('COM_EASYSTAGING_JSON_TEST_REMOTE_HOST_CONTACTED');

						// Get our DB object
						try
						{
							$target_db = JDatabase::getInstance($options);
						}
						catch (JDatabaseException $e)
						{
							print_r($e);
						}

						// Check for old style error
						$errNo = $target_db->getErrorNum();

						if ($errNo != 0)
						{
							$msg .= $target_db->getErrorMsg(true);
							/*
							 * @todo convert these error messages to JText::sprintf() versions
							 */
							if ($target_db->name == 'mysqli')
							{
								$mysqli = new mysqli($options['host'], $options['user'], $options['password'], null, $options['port'], $options['socket']);
								$errNo = $mysqli->connect_errno;
								$msg .= '<pre>' . $mysqli->connect_error . '</pre><br>';
							}
							elseif ($target_db->name == 'mysql')
							{
								$msg .= '<pre>' . mysql_error($target_db->getConnection()) . '</pre><br>';
							}
							else
							{
								$msg .= '<pre>Un-handled DB type: ' . $target_db->name . '</pre><br>';
							}

							$result = $errNo;
						}
						else
						{
							$msg .= JText::_('COM_EASYSTAGING_JSON_TEST_REMOTE_DATABASE_CONNECTION_OK');
							$status = 1;
							$result = $errNo;
						}
					}
					else
					{
						$msg .= JText::_('COM_EASYSTAGING_JSON_TEST_REMOTE_HOST_TIMEDOUT');
					}
				}
				else
				{
					$msg .= JText::_('COM_EASYSTAGING_JSON_TEST_REMOTE_HOST_INVALID');
				}
			}
		}

		$response = array('status' => $status, 'result' => $result, 'msg' => $msg);
		echo json_encode($response);
	}

	/**
	 * Status is the only valid entry point for the 1.1 architecture, it acts as the gateway and mediator.
	 *
	 * @since  1.1.0
	 *
	 * @return  null
	 */
	public function status()
	{
		// Check for request forgeries
		if (!JSession::checkToken('request'))
		{
			$response = array(
				'status' => '0',
				'error' => JText::_('JINVALID_TOKEN'),
			);
		}
		else
		{
			// Better check our use has permissions to run this plan and isn't swizzling the JS on the browser...
			$jIn = JFactory::getApplication()->input;
			$plan_id = $this->_plan_id();

			if ($this->_areWeAllowed($plan_id))
			{
				// Do we have a run ticket?
				$runTicket = $jIn->get('runticket', '');

				if (!$runTicket || is_null($runTicket))
				{
					// Get our --dry-run flag
					$dry_run = $jIn->getInt('es_drfca', 0);

					// Ok what steps are required?
					$stepsRequired = $jIn->get('step', null);
					$validSteps = array('startFile', 'startDBase', 'startAll');

					if ($stepsRequired != null && in_array($stepsRequired, $validSteps))
					{
						$response = $this->createRun($plan_id, $stepsRequired, $dry_run);
					}
					else
					{
						$response = array(
							'status' => self::NOTRUNNING,
							'error' => JText::_('COM_EASYSTAGING_PLAN_NO_STEP_REQUIRED'),
						);
					}
				}
				else
				{
					if ($this->runTicketIsValid($runTicket))
					{
						// Hey we have a run ticket and we're logged in let's get this programm his status
						$stepDetails = $this->getUnreportedRunSteps($runTicket);

						if (count($stepDetails))
						{
							$updates = isset($stepDetails['updates']) ? $stepDetails['updates'] : '';
							$running = isset($stepDetails['running']) ? $stepDetails['running'] : '';
							$left = isset($stepDetails['left']) ? $stepDetails['left'] : '';
							$status = $left ? self::PROCESSING : self::FINISHED;
							$response = array(
								'msg'     => JText::_('COM_EASYSTAGING_PLAN_JSON_IS_RUNNING'),
								'status'  => $status,
								'updates' => $updates,
								'running' => $running,
								'stepsleft'    => $left,
							);
						}
						else
						{
							$response = array(
								'msg'    => JText::_('COM_EASYSTAGING_RUN_FINISHED'),
								'status' => self::FINISHED,
								'runticket' => $runTicket,
							);
						}
					}
					else
					{
						$response = array(
							'status' => self::NOTRUNNING,
							'error' => JText::_('COM_EASYSTAGING_PLAN_JSON_NO_VALID_RUN_TICKET'),
						);
					}
				}
			}
			else
			{
				$response = array(
					'status' => self::NOTRUNNING,
					'error' => JText::_('COM_EASYSTAGING_PLAN_YOU_DO_NOT_HAVE_PERM'),
				);
			}
		}

		// Before we leave close off our logfile
		if (isset($this->logFile))
		{
			fclose($this->logFile);
		}

		echo json_encode($response);
	}

	/**
	 * Creates the Plan run, returning an array that includes the unique run ticket.
	 *
	 * @param   int     $plan_id        The ID of the plan the user is in.
	 *
	 * @param   string  $stepsRequired  The steps that need to be setup.
	 *
	 * @param   int     $dry_run        The flag to indicate an rsync --dry-run
	 *
	 * @return  array   The results array with any step details.
	 *
	 * @since   1.1.0
	 */
	protected function createRun($plan_id, $stepsRequired, $dry_run)
	{
		if ($plan_id)
		{
			// Get the plan
			/** @var $thePlan EasyStagingTablePlan */
			$thePlan = PlanHelper::getPlan($plan_id);

			// Is the plan already running i.e. is it's state 2
			if ($thePlan && ($thePlan->published == 1))
			{
				// Plan isn't running so create a run ticket.
				$rt_uuid = uniqid();
				$rt_dts = date('YmdHi');
				$runticket = $plan_id . '-' . $rt_dts . '-' . $rt_uuid;

				// Store the UUID for later validation of run enquiries
				$jAp = JFactory::getApplication();
				$jAp->setUserState('rt_uuid', $rt_uuid);

				// Add the runs steps to be executed
				if (($response = $this->createSteps($stepsRequired, $thePlan, $runticket, $dry_run)) && $response['status'] == 2)
				{
					// Extract our steps from the response
					$steps = $response['steps'];

					// Ok, we have our steps, lets change the state of the plan
					$thePlan->published = self::RUNNING;
					$thePlan->store();

					// Ok lets add our steps to the DB for PlanRunner to use
					if (!$this->stashRunSteps($steps))
					{
						$response['status'] = 0;
						$response['error']  = JText::_('COM_EASYSTAGING_JSON_COULDNT_STORE_STEPS');
					}

					if ($response['status'])
					{
						// Finally we launch our server side cli app
						$cmdPath = JPATH_SITE . '/cli/easystaging_plan_runner.php --runticket=' . $runticket;
						$runSIBResult = $this->_runScriptInBackground($cmdPath);
						$runnerCMD = $runSIBResult['cmd'];
						$ok = $runSIBResult['status'];
						$output = $runSIBResult['output'];

						if ($ok)
						{
							if ($this->params->get('run_script_with', 'AT') == 'AT')
							{
								$resultText = JText::sprintf('COM_EASYSTAGING_PLAN_RUNNER_LAUNCHED_AT_ID_X', $ok);
							}
							else
							{
								$resultText = JText::sprintf('COM_EASYSTAGING_PLAN_RUNNER_LAUNCHED_X_Y_Z', $runnerCMD, $ok, implode('|', $output));
							}

							$steps[] = array('action_type' => 99, 'result_text' => $resultText);
						}
						else
						{
							// Oh no couldn't launch the Plan Runner
							$lastline = $runSIBResult['lastline'];
							$cmdPath = $runSIBResult['cmdpath'];
							$response['status'] = 0;
							$response['error']  = JText::sprintf(
								'COM_EASYSTAGING_PLAN_RUNNER_LAUNCH_FAILED_X_Y_Z',
								$ok,
								implode('|', $output),
								$lastline,
								$runnerCMD,
								$cmdPath,
								$runticket
							);

							// Set the plan back to published
							$thePlan->published = self::PUBLISHED;
							$thePlan->store();
						}

						// Add the steps to the updates to be sent back to the browser...
						$response['updates'] = $steps;
					}
				}
			}
			elseif ($thePlan && ($thePlan->published == 2))
			{
				// Ok Plan is in the 'run' state, we say hey sorry someone else is running this plan
				$response = array(
					'status' => 0,
					'error' => JText::_('COM_EASYSTAGING_JSON_PLAN_ALREADY_IN_USE'),
				);
			}
			else
			{
				// Plan isn't published...
				$response = array(
					'error'    => JText::_('COM_EASYSTAGING_CANT_RUN_NOT_PUBLSIHED'),
					'status' => 0,
				);
			}
		}
		else
		{
			// No matching plan, Odd, but theoretically possible (would have to hack browser JS etc)
			$response = array(
				'status' => '0',
				'error' => JText::_('COM_EASYSTAGING_NO_PLAN_ID_AVAIL')
			);
		}

		return $response;
	}

	/**
	 * In here we add the steps to the steps table tagged with the runticket.
	 *
	 * @param   string  $stepsRequired  The steps required by this plan run.
	 *
	 * @param   JTable  $thePlan        The EasyStagingTablePlan that we're going to run.
	 *
	 * @param   string  $runticket      This runticket.
	 *
	 * @param   int     $dry_run        The flag to indicate an rsync --dry-run
	 *
	 * @return  array
	 *
	 * @since   1.1.0
	 */
	protected function createSteps($stepsRequired, $thePlan, $runticket, $dry_run)
	{
		// First we setup some basic required to create our steps
		$localSite  = PlanHelper::getLocalSite($thePlan->id);
		$remoteSite = PlanHelper::getRemoteSite($thePlan->id);

		// Create our root action
		$steps = array();
		$steps[] = array(
			'runticket' => $runticket,
			'action_type' => 0,
			'state' => self::WAITING,
			'result_text' => JText::_('COM_EASYSTAGING_JSON_ROOT_ACTION_MSG'),
		);
		$this->_writeMsgToLog($steps[0]['result_text'], $runticket);

		// We assume success, but we will fail if any step goes wrong...
		// Where "Goes wrong" means a serious breakdown... not just a single copy failing...
		$response   = array(
			'status' => self::FINISHED,
			'msg' => JText::_('COM_EASYSTAGING_JSON_CREATING_RUN_STEPS')
		);
		$this->_writeMsgToLog($response['msg'], $runticket);
		$rsyncSteps  = false;
		$tableSteps = false;
		$totalSteps = 0;

		// Get our Rsync steps
		if (($stepsRequired == 'startFile') || ($stepsRequired == 'startAll'))
		{
			$rsyncSteps = $this->createRsyncSteps($runticket, $localSite, $remoteSite, $dry_run);

			if (is_array($rsyncSteps))
			{
				$steps = array_merge($steps, $rsyncSteps);
				$totalSteps = count($rsyncSteps);
			}
			else
			{
				$msg = JText::_('COM_EASYSTAGING_JSON_FILE_DIRECTORIES_NOT_SETUP');
				$steps[] = array(
					'runticket' => $runticket,
					'action_type' => 99,
					'state' => self::FINISHED,
					'result_text' => $msg,
				);
				$this->_writeMsgToLog($msg, $runticket);
			}
		}
		else
		{
			$msg = JText::_('COM_EASYSTAGING_JSON_FILE_SYNC_NOT_REQUESTED');
			$steps[] = array(
				'runticket' => $runticket,
				'action_type' => 99,
				'state' => self::FINISHED,
				'result_text' => $msg,
			);
			$this->_writeMsgToLog($msg, $runticket);
		}

		// Get our Table steps
		if (($stepsRequired == 'startDBase') || ($stepsRequired == 'startAll' || ($stepsRequired == 'status')))
		{
			$tableSteps = $this->createCopyTableSteps($thePlan, $runticket, $localSite, $remoteSite);

			if (is_array($tableSteps))
			{
				$steps = array_merge($steps, $tableSteps);
				$totalSteps += count($tableSteps);
			}
			else
			{
				$msg = JText::_('COM_EASYSTAGING_JSON_TABLE_ACTIONS_NOT_REQUIRED');
				$steps[] = array(
					'runticket' => $runticket,
					'action_type' => 99,
					'state' => self::FINISHED,
					'result_text' => $msg,
				);
				$this->_writeMsgToLog($msg, $runticket);
			}
		}

		// Did we actually create any steps?s
		if (!$rsyncSteps && !$tableSteps)
		{
			$response['status'] = self::NOTRUNNING;
			$response['error']  = JText::_('COM_EASYSTAGING_JSON_NO_STEPS_CREATED_FOR_PLAN');
			$this->_writeMsgToLog($response['error'], $runticket);
		}
		else
		{
			$response['status']    = self::RUNNING;
			$response['msg']       = JText::sprintf('COM_EASYSTAGING_JSON_X_STEPS_CREATED_FOR_PLAN', $totalSteps);
			$response['runticket'] = $runticket;
			$response['steps']     = $steps;
			$this->_writeMsgToLog($response['msg'], $runticket);
		}

		return $response;
	}

	/**
	 * Creates the steps for the rsync if it's setup
	 *
	 * @param   string  $runticket   The current runticket to tag steps with.
	 *
	 * @param   JTable  $localSite   The local site object.
	 *
	 * @param   JTable  $remoteSite  The remote site object.
	 *
	 * @param   int     $dry_run     The flag to indicate an rsync --dry-run
	 *
	 * @return  bool|array
	 *
	 * @since   1.1.0
	 */
	protected function createRsyncSteps ($runticket, $localSite, $remoteSite, $dry_run)
	{
		// Setup our Rsync step, if we have local and remote paths
		if (($localSite->site_path != '') && ($remoteSite->site_path != ''))
		{
			// Get our FileCopy Actions
			// Setup query to retreive our rsync settings for this plan
			$db    = JFactory::getDbo();
			$query = $db->getQuery(true);
			$query->select($db->quoteName('id'));
			$query->select($db->quoteName('label'));
			$query->select($db->quoteName('direction'));
			$query->select($db->quoteName('source_path'));
			$query->select($db->quoteName('target_path'));
			$query->from($db->quoteName('#__easystaging_rsyncs'));
			$query->where($db->quoteName('plan_id') . ' = ' . $db->quote($this->_plan_id()));
			$db->setQuery($query);

			// Finally we can get our list of file copy actions for this plan
			$steps = array();

			if ($fileCopyActions = $db->loadAssocList())
			{
				// To each returned row we need to add a runticket and covert the raw action upto a plan action
				foreach ($fileCopyActions as $row)
				{
					// Check if our options need --dry-run
					$rsync_options = RunHelper::checkRsyncOptions($localSite->rsync_options, $dry_run);

					// Build our file copy aciton
					$newAction['id']               = $row['id'];
					$newAction['local_site_path']  = $localSite->site_path;
					$newAction['remote_site_path'] = $remoteSite->site_path;
					$newAction['rsync_options']    = $rsync_options;
					$newAction['file_exclusions']  = $localSite->file_exclusions;
					$newAction['label']            = $row['label'];
					$newAction['direction']        = $row['direction'];
					$newAction['source_path']      = $row['source_path'];
					$newAction['target_path']      = $row['target_path'];

					// Bundle it into our plan step
					$step = array(
						'runticket' => $runticket,
						'action_type' => intval($row['direction']) + 1,
						'action' => json_encode($newAction),
						'result_text' => JText::sprintf('COM_EASYSTAGING_JSON_RSYNC_STEP_X_ADDED', $row['label'])
					);

					// Add it to our collection of steps
					$steps[] = $step;

					// Log it
					$this->_writeMsgToLog($step['result_text'], $runticket);
				}
			}

			// Now all we have to do is return the steps
			$steps = count($steps) ? $steps : false;

		}
		else
		{
			$steps = false;
		}

		return $steps;
	}

	/**
	 * Creates the table actions.
	 *
	 * @param   EasystagingTablePlan  $thePlan    The current plan.
	 *
	 * @param   string                $runticket  The runticket.
	 *
	 * @return  bool|array
	 *
	 * @since   1.1.0
	 *
	 * @todo Creates a step for each table
	 */
	protected function createCopyTableSteps($thePlan, $runticket)
	{
		// Setup query to retreive our tables settings for this plan
		$db    = JFactory::getDbo();
		$query = $db->getQuery(true);
		$query->select($db->quoteName('tablename') . ' as ' . $db->quoteName('action'));
		$query->select($db->quoteName('action') . ' as ' . $db->quoteName('action_type'));
		$query->from($db->quoteName('#__easystaging_tables'));
		$query->where($db->quoteName('plan_id') . ' = ' . $db->quote($thePlan->id));
		$query->where($db->quoteName('action') . ' NOT LIKE ' . $db->quote(0));
		$db->setQuery($query);

		// Finally we can get our list of tables for this plan
		$steps = array();

		if ($theTables = $db->loadAssocList())
		{
			// To each returned row we need to add a runticket and covert the table action upto a plan action
			foreach ($theTables as $row)
			{
				$row['runticket'] = $runticket;
				$row['action_type'] = $row['action_type'] + 10;
				$row['result_text'] = JText::sprintf('COM_EASYSTAGING_JSON_TABLE_X_STEP_ADDED', $row['action'], $thePlan->name);
				$steps[] = $row;
			}
		}

		// Now all we have to do is return the steps
		$steps = count($steps) ? $steps : false;

		return $steps;
	}

	/**
	 * Retreives any unreported or yet to execute steps.
	 *
	 * @param   string  $runticket  The unique ticket for this run.
	 *
	 * @return  array   An array of steps that may have been run or have yet to be run.
	 *
	 * @since   1.1.0
	 */
	protected function getUnreportedRunSteps($runticket = null)
	{
		$response = array();

		// It seems redundant but we have one case of this happening
		if (!is_null($runticket))
		{
			// Retreive all of our run steps that haven't been reported

			// Get the finished but unreported records
			$unreportedCompletedSteps = $this->getRunSteps($runticket, self::FINISHED, self::NOTREPORTED);

			if ($unreportedCompletedSteps && count($unreportedCompletedSteps))
			{
				$response['updates'] = $unreportedCompletedSteps;
				$this->markStepsReported($unreportedCompletedSteps);
			}

			// Get the step being processed
			$runningSteps = $this->getRunSteps($runticket, self::RUNNING, self::NOTREPORTED);

			if ($runningSteps && count($runningSteps))
			{
				$response['running'] = $runningSteps;
				$this->markStepsReported($runningSteps);
			}

			// Get the remaining and unreported records
			$remainingSteps = $this->getRunSteps($runticket, self::WAITING);

			if ($remainingSteps && count($remainingSteps))
			{
				$response['left'] = $remainingSteps;
			}
		}

		return $response;
	}

	/**
	 * Generic step retreival.
	 *
	 * @param   string  $runticket  The active ticket identifier.
	 *
	 * @param   int     $state      State of the step 0 - waiting to be processed
	 *                                                1 - processed
	 *                                                2 - processing
	 * @param   int     $reported   1 for completed steps that have already been reported to the browser
	 *
	 * @return  array
	 */
	protected function getRunSteps($runticket, $state, $reported=null)
	{
		// Retreive all of our run steps that haven't been reported
		$db = JFactory::getDbo();

		// Get the finished but unreported records
		$query = $db->getQuery(true);
		$query->select('*')->from($db->quoteName('#__easystaging_steps'));
		$query->where($db->quoteName('runticket') . ' = ' . $db->quote($runticket));
		$query->where($db->quoteName('state') . ' = ' . $db->quote($state));

		if (!is_null($reported))
		{
			$query->where($db->quoteName('reported') . ' = ' . $db->quote($reported));
		}

		$query->order($db->quote('id'));
		$db->setQuery($query);

		return $db->loadAssocList();
	}

	/**
	 * Adds the steps handed in to the steps table for the plan runner to use.
	 *
	 * @param   array  $steps  The array of steps to be inserted
	 *
	 * @return  mixed
	 *
	 * @since   1.1.0
	 */
	protected function stashRunSteps($steps)
	{
		$db = JFactory::getDbo();
		$query = $db->getQuery(true);
		$query->insert($db->quoteName('#__easystaging_steps'));
		$query->columns(
			array(
				$db->quoteName('runticket'),
				$db->quoteName('action_type'),
				$db->quoteName('action'),
				$db->quoteName('state'),
				$db->quoteName('result_text')
			)
		);

		foreach ($steps as $row)
		{
			$values = array();
			$values[] = isset($row['runticket']) ? $db->quote($row['runticket']) : "''";
			$values[] = isset($row['action_type']) ? $db->quote($row['action_type']) : "''";
			$values[] = isset($row['action']) ? $db->quote($row['action']) : "''";
			$values[] = isset($row['state']) ? $db->quote($row['state']) : "''";
			$values[] = isset($row['result_text']) ? $db->quote($row['result_text']) : "''";

			$query->values(implode(', ', $values));
		}

		$db->setQuery($query);

		return $db->execute();
	}

	/**
	 * Sets the reported flag of the steps.
	 *
	 * @param   array  $steps  Array of steps
	 *
	 * @return  mixed  A database cursor resource on success, boolean false on failure.
	 */
	protected function markStepsReported($steps)
	{
		// Set up some defaults
		$db = JFactory::getDbo();
		$query = $db->getQuery(true);
		$stepsToUpdate = array();
		$open = $db->quoteName('id') . ' = ';

		foreach ($steps as $step)
		{
			$stepsToUpdate[] = $open . $db->quote($step['id']);
		}

		$query->update($db->quoteName('#__easystaging_steps'));
		$query->set($db->quoteName('reported') . ' = ' . $db->quote(self::REPORTED));
		$query->set($db->quoteName('result_text') . ' = NULL');
		$query->where($stepsToUpdate);
		$db->setQuery($query);

		return $db->execute();
	}

	/**
	 * Checks that the ticket is of a valid format, the plan is published and the UUID matches the users sessions store.
	 *
	 * @param   string  $runticket  The run ticket string. PlanId-DTS-UUID
	 *
	 * @return  boolean
	 */
	protected function runTicketIsValid($runticket)
	{
		// Assume bad people are out to get us.
		$result = false;

		// Check our run ticket structure
		$rtarray = explode('-', $runticket);

		if (count($rtarray) == 3)
		{
			list($plan_id, , $rt_uuid) = $rtarray;

			// Get the plan
			/** @var $thePlan EasyStagingTablePlan */
			$thePlan = PlanHelper::getPlan($plan_id);

			// Does the plan exist and is it published or running? (Both are OK)
			if ($thePlan && (($thePlan->published == self::PUBLISHED) || ($thePlan->published == self::RUNNING)))
			{
				$jAp = JFactory::getApplication();

				// Does the UUID match the one in this users Session?
				if ($rt_uuid == $jAp->getUserState('rt_uuid'))
				{
					$result = true;
				}
			}
		}

		return $result;
	}

	/**
	 * Helper.
	 *
	 * @param   string  $msg        The line to be written to the log file.
	 * @param   string  $runticket  The runticket whose log file we're writting to.
	 *
	 * @return  null
	 */
	private function _writeMsgToLog($msg, $runticket)
	{
		$response = array(
			'msg' => $msg,
			'status' => 1,
		);
		$this->_writeToLog($response, $runticket);
	}

	/**
	 * Centralised logging method.
	 *
	 * @param   array   $response   Array of details.
	 *
	 * @param   string  $runTicket  The runticket whose log file we're writting to.
	 *
	 * @return bool|int
	 */
	private function _writeToLog($response, $runTicket)
	{
		$logWriteResult = false;
		$runDirectory = RunHelper::get_run_directory($runTicket);

		if ($response['status'])
		{
			$logLine = $response['msg'];
		}
		else
		{
			$logLine = $response['error'];
		}

		if (!is_array($runDirectory))
		{
			if (!isset($this->logFile) || ($this->logFile === false))
			{
				$logFileName = 'es-log-plan-run-' . $runTicket . '.txt';
				$fullPathToLogFile = JPATH_COMPONENT_ADMINISTRATOR . '/syncfiles/' . $runTicket . '/' . $logFileName;

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
	 * Getter for plan_id
	 *
	 * @return int
	 */
	private function _plan_id()
	{
		if (!isset($this->plan_id))
		{
			$jinput = JFactory::getApplication()->input;
			$this->plan_id = $jinput->get('plan_id', 0, 'INT');
		}

		return $this->plan_id;
	}

	/**
	 * Make sure the currently logged in user is allowed to run plans
	 *
	 * @param   int  $plan_id  The current plan.
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
	 * Runs the script in the background by scheduling it with the `at` daemon and returns the result
	 *
	 * @param   string  $pathToScript  A path to the script to run e.g. "/path/to/my/cli/app.php"
	 *
	 * @return  int
	 */
	private function _runScriptInBackground($pathToScript)
	{
		if (JDEBUG)
		{
			jimport('joomla.log.log');
			JLog::addLogger(array('text_file' => 'com_easystaging.log.php'), JLog::ALL, 'com_easystaging');
		}

		// Get the path to php from defined settings
		$php_quiet = $this->params->get('php_quiet', '');
		$php_file = $this->params->get('php_file', '');
		$pathToPHP = $this->params->get('path_to_php', '');
		$capture_PHP_out_from_AT = $this->params->get('php_out_captured', 0);

		$cmdPath = $pathToPHP;
		$cmdPath .= $php_quiet ? ' ' . $php_quiet : '';
		$cmdPath .= $php_file ? ' ' . $php_quiet : '';
		$cmdPath .= ' ' . $pathToScript;

		if (JDEBUG)
		{
			JLog::add('CmdPath: ' . $cmdPath, JLog::WARNING);
		}

		// Which way are we going to launch this?
		$run_script_with = $this->params->get('run_script_with', 'AT');

		if ($run_script_with == 'AT')
		{
			$cap_php_out = $capture_PHP_out_from_AT ? " > components/com_easystaging/syncfiles/pr.log.txt" : "";

			// We need '2>&1' so we have something to pass back
			$cmd = sprintf('echo "%s' . $cap_php_out . '" | at now 2>&1', $cmdPath);
		}
		else
		{
			$cmd = sprintf('%s 2>&1 &', $cmdPath);
		}

		if (JDEBUG)
		{
			JLog::add('Cmd: ' . $cmd, JLog::WARNING);
		}

		/**
		 * On some versions of PHP if you don't define $output and $returnValue before hand
		 * the exec() call will not give you output or return values (even if the damm examples
		 * say otherwise on php.net)
		 */
		$output = array();
		$returnValue = '';
		$lastLine = exec($cmd, $output, $returnValue);

		if (JDEBUG)
		{
			JLog::add('Last Line: ' . $lastLine, JLog::WARNING);
			JLog::add('Return Value: ' . $returnValue, JLog::WARNING);
			JLog::add('Output: ' . print_r($output, true), JLog::WARNING);
		}

		if (($run_script_with == 'AT') && ($returnValue == 0))
		{
			// All good anything else is bad
			$returnValue = $output[0];
		}
		elseif($run_script_with == 'AT')
		{
			$returnValue = false;
		}
		elseif($run_script_with == "DIRECT" && $returnValue === 0 && empty($output) && $lastLine == '')
		{
			// Some setups (aka SiteGround/Hive servers) don't return any values from exec(), normal situations
			// will always put something in $returnValue or $lastLine
			$returnValue = true;
		}

		$result = array('cmd' => $cmd, 'cmdpath' => $cmdPath, 'status' => $returnValue, 'output' => $output, 'lastline' => $lastLine);

		return $result;
	}

	/**
	 * Contacts host using fsockopen, defaults to http and 3 seconds.
	 *
	 * @param   string  $domain   A domain or IP address that can be contacted
	 * @param   int     $port     The TCP/IP Port to use.
	 * @param   int     $timeout  How long shall we wait for a response?
	 *
	 * @return float|int|mixed
	 */
	private function contactHost($domain, $port = 80, $timeout = 3)
	{
		$starttime = microtime(true);
		$file      = fsockopen($domain, $port, $errno, $errstr, $timeout);
		$stoptime  = microtime(true);

		// Site is down
		if (!$file)
		{
			$status = false;
		}
		else
		{
			fclose($file);
			$status = true;
			$dur = ($stoptime - $starttime) * 1000;
		}

		if (JDEBUG)
		{
			JLog::add('Contacting Host: ' . $domain, JLog::WARNING);
			JLog::add('Status: ' . $status, JLog::WARNING);
			JLog::add('Duration: ' . $dur, JLog::WARNING);
		}

		return $status;
	}
}
