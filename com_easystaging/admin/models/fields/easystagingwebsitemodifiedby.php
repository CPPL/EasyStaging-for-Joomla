<?php
/**
 * @package    EasyStaging
 * @author     Craig Phillips <craig@craigphillips.biz>
 * @copyright  Copyright (C) 2016 Craig Phillips Pty Ltd.
 * @license    GNU/GPL http://www.gnu.org/copyleft/gpl.html
 * @url        http://www.seepeoplesoftware.com
 */

defined('JPATH_PLATFORM') or die;

JFormHelper::loadFieldClass('list');

/**
 * Form Field to load a list of EasyStaging Plan Creators
 *
 * @since  2.0
 */
class JFormFieldEasyStagingWebsiteModifiedBy extends JFormFieldList
{
    /**
     * The form field type.
     *
     * @var    string
     * @since  2.0
     */
    public $type = 'Plan Created By';

    /**
     * Cached array of items.
     *
     * @var    array
     * @since  3.2
     */
    protected static $options = array();

    /**
     * Method to get the options to populate list
     *
     * @return  array  The field option objects.
     *
     * @since   3.2
     */
    protected function getOptions()
    {
        // Accepted modifiers
        $hash = md5($this->element);

        if (!isset(static::$options[$hash]))
        {
            static::$options[$hash] = parent::getOptions();

            $db = JFactory::getDbo();

            // Construct the query
            $query = $db->getQuery(true)
                ->select('u.id AS value, u.name AS text')
                ->from('#__users AS u')
                ->join('INNER', '#__easystaging_sites AS s ON s.modified_by = u.id')
                ->group('u.id, u.name')
                ->order('u.name');

            // Setup the query
            $db->setQuery($query);

            // Return the result
            if ($options = $db->loadObjectList())
            {
                static::$options[$hash] = array_merge(static::$options[$hash], $options);
            }
        }

        return static::$options[$hash];
    }
}
