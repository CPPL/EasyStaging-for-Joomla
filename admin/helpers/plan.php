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

	private function _loadLocalSiteRecord($plan_id)
	{
		$type = 1; // Local site
		return $this->_loadSiteRecord($plan_id, $type);
	}
	private function _loadRemoteSiteRecord($plan_id)
	{
		$type = 2; // Live/Target site
		return $this->_loadSiteRecord($plan_id, $type);
	}
	private function _loadSiteRecord($plan_id, $type)
	{
		// Load our site record
		JTable::addIncludePath(JPATH_ADMINISTRATOR.'/components/com_easystaging/tables');
		$Sites = JTable::getInstance('Site', 'EasyStagingTable');
	
		if($Sites->load(array('plan_id'=>$plan_id, 'type'=>$type)))
		{
			return $Sites;
		} else {
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
	public static function getActions($plan_id = 0)
	{
		$user	= JFactory::getUser();
		$result	= new JObject;
	
		if (empty($plan_id)) {
			$assetName = self::$extension;
		}
		else {
			$assetName = self::$extension . '.plan.' . (int) $plan_id;
		}
	
		$actions = array(
				'core.admin', 'core.manage', 'easystaging.create', 'easystaging.edit', 'easystaging.edit.own', 'easystaging.edit.state', 'easystaging.delete', 'easystaging.run'
		);
	
		foreach ($actions as $action) {
			$result->set($action,	$user->authorise($action, $assetName));
		}
	
		return $result;
	}
	
}
