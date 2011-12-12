<?php
/* @package		EasyStaging
 * @link		http://seepeoplesoftware.com
 * @license		GNU/GPL
 * @copyright	Craig Phillips Pty Ltd
*/
 
// No direct access
 
defined( '_JEXEC' ) or die( 'Restricted access' );
 
// import Joomla controllerform library
jimport('joomla.application.component.controller');
jimport( 'joomla.database.table' );
 
/**
 * EasyStaging Component Plan Controller
 */
class EasyStagingControllerPlan extends JController
{
	function hello()
	{
		// Check for request forgeries
		if ($this->_tokenOK() && ($plan_id = $this->_plan_id())) {
			echo json_encode(array('msg' => 'EasyStaging is ready.', 'status' => 1));
		} else {
			echo json_encode(array('msg' => 'Plan ID is missing.', 'status' => 0));
		}
	}
	
	function doRsyncStep01()
	{
		// Check for request forgeries
		if ($this->_tokenOK() && ($plan_id = $this->_plan_id())) {
			// $plan_id = JRequest::get('plan_id');
			if($rsResult = $this->_createRSYNCExclusionFile($plan_id))
			{
				$fileCreated = $rsResult['fileName'];
				echo json_encode(array('msg' => JText::sprintf('COM_EASYSTAGING_JSON_RSYNC_STEP_SUCCEEDED', $fileCreated), 'status' => 1, 'data' => $fileCreated));
			} else {
				echo json_encode(array('msg' => JText::sprintf('COM_EASYSTAGING_JSON_RSYNC_STEP_FAILED', $plan_id), 'status' => 0));
			}
		}
	}

	function doRsyncStep02()
	{
		// Check for request forgeries
		if ($this->_tokenOK() && ($plan_id = $this->_plan_id())) {
			$rsyncCmd = 'rsync -avr';
	
			$rsyncOutput = array();
			// exec($rsyncCmd, $rsyncOutput);
			echo json_encode(array('msg' => 'RSYNC Step 02 for Plan ID: '.$plan_id, 'status' => 1));
		}
	}
	
	private function _createRSYNCExclusionFile($plan_id)
	{
		if(isset($plan_id))
		{
			// Load our site record
			JTable::addIncludePath(JPATH_ADMINISTRATOR.'/components/com_easystaging/tables');
			$Sites = JTable::getInstance('Site', 'EasyStagingTable');
			$site  = $Sites->load(array('plan_id'=>$plan_id, 'type'=>'1'));
			
			// Build our file path & file handle
			$pathToExclusionsFile = $this->_excl_file_path().$this->_excl_file_name();
			$result = array('fileName' => $this->_excl_file_name());
			$exclusionFile = fopen($pathToExclusionsFile, 'w');
			
			// Create the content for our exclusions file
			$defaultExclusions = <<< EOH
-tmp/
-logs/
-cache/
-configuration.php

EOH;

			// Combine the default exclusions with those in the local site record
			$allExclusions = $defaultExclusions.$this->_checkExclusionField($Sites->file_exclusions);
			
			// Attempt to write the file
			$result['status'] = fwrite($exclusionFile, $allExclusions);
			$result['msg'] = $result['status'] ? JText::sprintf('File written successfully (%s bytes)',$result['status']) : JText::_('Failed to write exclusions file') ;
			
			// Time to close off
			fclose($exclusionFile);
			
			// Return to Maine, where the moose, deer, eagles and loons roam.
			return $result;
		}

		return false;
	}

	private function _excl_file_path()
	{
		return JPATH_ADMINISTRATOR.'/components/com_easystaging/syncfiles/';
	}
	private function _excl_file_name()
	{
		return ('plan-'.$this->_plan_id().'-exclusions.txt');
	}
	
	private function _plan_id()
	{
		$jinput =  JFactory::getApplication()->input;
		$plan_id = $jinput->get('plan_id', 0, 'INT');
		return $plan_id;
	}

	/**
	 * Checks $file_exclusions to ensure each line starts with a "-" as required by rsync ...
	 * @param string $file_exclusions
	 * @return string|boolean - false on failure
	 */
	private function _checkExclusionField($file_exclusions)
	{
		if(isset($file_exclusions) && ($file_exclusions != ''))
		{
			$result = array();
			$file_exclusions = explode("\n", str_replace("\r\n", "\n", $file_exclusions)); // Just in case, we convert all \n\r before exploding
			foreach ($file_exclusions as $fe_line) {
				$fe_line = trim($fe_line);
				if($fe_line[0] != "-") $fe_line = '-'.$fe_line;
				$result[] = $fe_line;
			}
			return implode("\n", $result);
		} else {
			return false;
		}
	}
	
	private function _getRsyncOptions($plan_id)
	{
		//place holder, will get from plan record
		$opts = 'avr';
		return ' -'.$opts;
	}
	
	private function _tokenOK()
	{
		// Check for request forgeries
		if (!JRequest::checkToken('request')) {
			$response = array(
						'status' => '0',
						'msg' => JText::_('JINVALID_TOKEN')
			);
			echo json_encode($response);
			return false;
		}

		return true;
	}
}
