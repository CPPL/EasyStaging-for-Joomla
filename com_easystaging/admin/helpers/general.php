<?php

// No direct access
defined('_JEXEC') or die;

/**
 * EasyStaging component helper.
 *
 * @package  EasyStaging
 *
 * @since    1.0.0
 *
 */
class ES_General_Helper
{
    /**
     * A general catch-all where we can check for essential settings, files etc.
     *
     * @param  string   $comp  The name of the component.
     *
     * @return boolean
     */
    public static function isEveryThingOK()
    {
        // Assume the BEST!
        $OK = true;

        // Get some basics
        $jAp    = JFactory::getApplication();
        $params = JComponentHelper::getParams('com_easystaging');

        // Check PHP path.
        $path_to_php = $params->get('path_to_php', '');

        if ($path_to_php == '') {
            $OK = false;
            $jAp->enqueueMessage(JText::_('COM_EASYSTAGING_PATH_TO_PHP_EMPTY'), 'ERROR');
            $jAp->enqueueMessage(JText::_('COM_EASYSTAGING_PATH_TO_PHP_NOTES'));
        }

        // Return our result
        return $OK;
    }
}
