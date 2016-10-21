<?php
/**
 * @package     EasyStaging
 * @link        http://seepeoplesoftware.com
 * @license     GNU/GPL
 * @copyright   Craig Phillips Pty Ltd
*/

defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.controllerform');

/**
 * Class EasyStagingControllerPlan
 * 
 * @since  1.0
 */
class EasyStagingControllerWebsite extends JControllerForm
{
    public function __construct($config = array())
    {
        parent::__construct($config);
    }
}
