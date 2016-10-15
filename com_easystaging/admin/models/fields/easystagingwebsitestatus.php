<?php
/**
 * @package    EasyStaging
 * @author     Craig Phillips <craig@craigphillips.biz>
 * @copyright  Copyright (C) 2016 Craig Phillips Pty Ltd.
 * @license    GNU/GPL http://www.gnu.org/copyleft/gpl.html
 * @url        http://www.seepeoplesoftware.com
 */

defined('JPATH_PLATFORM') or die;

JFormHelper::loadFieldClass('predefinedlist');

/**
 * Form Field to load a list of states
 *
 * @since  3.2
 */
class JFormFieldEasyStagingWebsiteStatus extends JFormFieldPredefinedList
{
    /**
     * The form field type.
     *
     * @var    string
     * @since  2.0
     */
    public $type = 'Plan Status';

    /**
     * Available statuses
     *
     * @var  array
     * @since  2.0
     */
    protected $predefinedOptions = array(
        '0'  => 'JUNPUBLISHED',
        '1'  => 'JPUBLISHED',
        '*'  => 'JALL'
    );
}
