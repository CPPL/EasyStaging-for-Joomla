<?php
defined('_JEXEC') or die('Restricted access');
 
jimport('joomla.application.component.controlleradmin');
 
/**
 * EasyStaging Plans Controller
 */
class EasyStagingControllerPlans extends JControllerAdmin
{
	/**
	 * Proxy for getModel.
	 *
	 * @param   string  $name    Model name
	 * @param   string  $prefix  Component prefix
	 * @param   array   $config  Config options.
	 *
	 * @since	1.6
	 * @return object
	 */
	public function getModel($name = 'Plan', $prefix = 'EasyStagingModel', $config = array('ignore_request' => true))
	{
		$model = parent::getModel($name, $prefix, $config);

		return $model;
	}
}
