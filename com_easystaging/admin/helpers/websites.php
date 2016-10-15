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
class WebsitesHelper
{
    public static $extension = 'com_easystaging';

    public static $base_assett = 'website';
    /**
     * Extracts the relevant items from the website
     *
     * @param object   $row
     * @param int      $i
     * @param JUser    $user
     * @param int      $userId
     *
     * @return array
     */
    public static function processWebsiteRow($row, $i, $user, $userId)
    {
        // Row State
        $canDo = PlanHelper::getActions($row->id);
        $checked = JHTML::_('grid.id', $i, $row->id);

        $published = JHtml::_('jgrid.published', $row->state, $i, 'plans.', $canDo->get('core.edit.state'), 'cb');
        $canCheckin = $user->authorise('core.manage', 'com_checkin') || $row->checked_out == $userId || $row->checked_out == 0;
        $canChange = $user->authorise('core.edit.state', 'com_easystaging.plan.' . $row->id) && $canCheckin;

        // Website
        if ($canDo->get('core.edit') && $canCheckin && $canChange) {
            $website = '<a href="' . JRoute::_('index.php?option=com_easystaging&task=website.edit&id=' . $row->id) . '" class="hasTooltip" title="'
                . JHtml::tooltipText(JText::_('COM_EASYSTAGING_MANAGER_WEBSITE_EDIT') . '::' . JText::sprintf('COM_EASYSTAGING_MANAGER_EDIT_WEBSITE_X', $row->site_name)) . '">'
                . $row->site_name . '</a>';
        } else {
            $website = $row->site_name;
        }

        if ($row->checked_out) {
            $website = JHtml::_('jgrid.checkedout', $i, $row->editor, $row->checked_out_time, 'plans.', $canCheckin) . ' ' . $website;
        }

        // Created by
        $creator = JFactory::getUser($row->modified_by)->name;
        $created = JHTML::_('date', $row->created, JText::_('DATE_FORMAT_LC1'));
        $created_by = JText::sprintf('COM_EASYSTAGING_MANAGER_CREATED_BY', $creator, $created);

        // Last Modified/by
        $modified = JHTML::_('date', $row->modified, JText::_('DATE_FORMAT_LC1'));
        $modified_by = JFactory::getUser($row->modified_by)->name;
        $last_modified_by = JText::sprintf('COM_EASYSTAGING_MANAGER_LAST_MODIFIED_BY', $modified_by, $modified);

        return array($checked, $published, $website, $created_by, $last_modified_by);
    }
}
