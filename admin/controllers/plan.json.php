<?php
/* @package		EasyStaging
 * @link		http://seepeoplesoftware.com
 * @license		GNU/GPL
 * @copyright	Craig Phillips Pty Ltd
*/
 
// No direct access
 
defined( '_JEXEC' ) or die( 'Restricted access' );
 
// import Joomla controllerform library
jimport('joomla.application.component.controllerform');
 
/**
 * EasyStaging Component Plan Controller
 */
class EasyStagingControllerPlan extends JController
{
	function hello()
	{
		echo json_encode('EasyStaging is ready.');
	}
}
