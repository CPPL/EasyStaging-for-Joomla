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
class PlansHelper
{
    public static $extension = 'com_easystaging';

    public static $base_assett = 'plan';

    private static $_ext_actions = array('easystaging.run');

    /**
     * Extracts the relevant items from the plan
     *
     * @param object   $row
     * @param int      $i
     * @param JUser    $user
     * @param int      $userId
     *
     * @return array
     */
    public static function processRow($row, $i, $user, $userId)
    {
        // Row State
        $canDo = PlanHelper::getActions($row->id);
        $checked = JHTML::_('grid.id', $i, $row->id);

        $published = JHtml::_('jgrid.published', $row->published, $i, 'plans.', $canDo->get('core.edit.state'), 'cb', $row->publish_up, $row->publish_down);
        $canCheckin = $user->authorise('core.manage', 'com_checkin') || $row->checked_out == $userId || $row->checked_out == 0;
        $canChange = $user->authorise('core.edit.state', 'com_easystaging.plan.' . $row->id) && $canCheckin;

        // Plan
        if ($canDo->get('core.edit') && $canCheckin && $canChange) {
            $plan = '<a href="' . JRoute::_('index.php?option=com_easystaging&task=plan.edit&id=' . $row->id) . '" class="hasTooltip" title="'
                . JHtml::tooltipText(JText::_('COM_EASYSTAGING_MANAGER_PLAN_EDIT') . '::' . JText::sprintf('COM_EASYSTAGING_MANAGER_EDIT_PLAN_X', $row->name)) . '">'
                . $row->name . '</a>';
        } elseif ($canDo->get('easystaging.run') && !$row->checked_out && $row->published) {
            $plan = '<a href="' . JRoute::_('index.php?option=com_easystaging&task=plan.run&id=' . $row->id) . '" class="hasTooltip"' . 'title="'
                . JHtml::tooltipText(JText::_('COM_EASYSTAGING_MANAGER_PLAN_RUN') . '::' . JText::sprintf('COM_EASYSTAGING_MANAGER_RUN_PLAN_X', $row->name)) . '">'
                . $row->name . '</a>';
        } else {
            $plan = $row->name;
        }

        if ($row->checked_out) {
            $plan = JHtml::_('jgrid.checkedout', $i, $row->editor, $row->checked_out_time, 'plans.', $canCheckin) . ' ' . $plan;
        }

        // Last Modified/by
        $modified = JHTML::_('date', $row->modified, JText::_('DATE_FORMAT_LC1'));
        $modified_by = JFactory::getUser($row->modified_by)->name;
        $last_modified_by = JText::sprintf('COM_EASYSTAGING_MANAGER_LAST_MODIFIED_BY', $modified_by, $modified);

        // Last Run
        $last_run = JHtml::_('date', $row->last_run, JText::_('DATE_FORMAT_LC1'));

        if ($row->last_run == '0000-00-00 00:00:00') {
            $last_run = JText::_('COM_EASYSTAGING_NOT_RUN');
            return array($checked, $published, $plan, $last_modified_by, $last_run);
        } else {
            if ($last_run != $row->lastRunDTS) {
                $relDate = $row->lastRunDTS;
            } else {
                $relDate = '';
            }

            if ($relDate == '') {
                $last_run = JText::sprintf('COM_EASYSTAGING_LAST_RUN_NO_REL_DATE', $last_run, $row->last_run_by);
                return array($checked, $published, $plan, $last_modified_by, $last_run);
            } else {
                $last_run = JText::sprintf('COM_EASYSTAGING_LAST_RUN', $last_run, $relDate, $row->last_run_by);
                return array($checked, $published, $plan, $last_modified_by, $last_run);
            }
        }
    }

}
