<?php
/**
 * Main Manager View for EasyStaging Component
 *
 * @package    EasyTable
 * @author     Craig Phillips <craig@craigphillips.biz>
 * @copyright  Copyright (C) 2012 Craig Phillips Pty Ltd.
 * @license    GNU/GPL http://www.gnu.org/copyleft/gpl.html
 * @url        http://www.seepeoplesoftware.com
 */

// No direct access

defined('_JEXEC') or die('Restricted access');

require_once JPATH_COMPONENT_ADMINISTRATOR . '/helpers/general.php';
require_once JPATH_COMPONENT . '/helpers/plans.php';
require_once JPATH_COMPONENT . '/helpers/plan.php';

/**
 * EasyStaging Manager View
 *
 */
class EasyStagingViewWebsites extends JViewLegacy
{
    protected $items;

    protected $pagination;

    protected $state;

    protected $current_version;

    /**
     * Our implementation of display()
     *
     * @param   string  $tpl  The name of the template file to parse; automatically searches through the template paths.
     *
     * @return  mixed  A string if successful, otherwise a JError object.
     */
    public function display($tpl = null)
    {
        JHtml::_('behavior.framework', true);
        JHtml::_('behavior.tooltip');
        JHtml::_('behavior.multiselect');

        // Get version
        $xml = simplexml_load_file(JPATH_BASE . '/components/com_easystaging/easystaging.xml');
        $this->current_version = (string) $xml->version;

        // Setup document (Toolbar, css, js etc)
        $this->addToolbar();
        $this->addCSSEtc();
        PlansHelper::addSubmenu('websites');

        // Get data from the model
        $items = $this->get('Items');

        $pagination = $this->get('Pagination');

        if (count($errors = $this->get('Errors'))) {
            JError::raiseError(500, implode('<br />', $errors));

            return false;
        }
        // Assign data to the view
        $this->items         = $items;
        $this->pagination    = $pagination;
        $this->state         = $this->get('State');
        $this->filterForm    = $this->get('FilterForm');
        $this->activeFilters = $this->get('ActiveFilters');
        $this->sidebar = JHtmlSidebar::render();

        parent::display($tpl);
    }

    /**
     * Returns an array of fields the table can be sorted by
     *
     * @return  array  Array containing the field name to sort by as the key and display text as value
     *
     * @since   2.0
     */
    protected function getSortFields()
    {
        return array(
            'status'     => JText::_('JSTATUS'),
            'type'       => JText::_('COM_EASYSTAGING_SITE_TYPE'),
            'site_name'  => JText::_('COM_EASYSTAGING_WEBSITE'),
            'created_by' => JText::_('COM_EASYSTAGING_FILTER_CREATOR'),
            'created'    => JText::_('JDATE'),
            'id'         => JText::_('JGRID_HEADING_ID'),
        );
    }

    /**
     * addToolbar()
     *
     * @return null
     *
     * @since 1.1
     */
    private function addToolbar()
    {
        JToolBarHelper::title(JText::_('COM_EASYSTAGING_EASYSTAGING_MANAGER'), 'easystaging');

        $canDo  = PlanHelper::getActions();

        if ($canDo->get('core.create')) {
            JToolBarHelper::addNew('plan.add');
        }

        if ($canDo->get('core.edit')) {
            JToolBarHelper::editList('plan.edit');
        }

        if ($canDo->get('core.edit.state')) {
            JToolBarHelper::divider();
            JToolBarHelper::publishList('plans.publish', 'JTOOLBAR_PUBLISH');
            JToolBarHelper::unpublishList('plans.unpublish', 'JTOOLBAR_UNPUBLISH');
        }

        if ($canDo->get('core.delete')) {
            JToolBarHelper::deleteList('', 'plans.delete');
            JToolBarHelper::divider();
        }

        if ($canDo->get('core.admin')) {
            JToolBarHelper::preferences('com_easystaging');
            JToolBarHelper::divider();
        }
    }

    /**
     * addCSSEtc()
     *
     * @return null
     *
     * @since 1.1
     */
    private function addCSSEtc()
    {
        // Get the document object
        $document = JFactory::getDocument();

        // First add CSS to the document
        $document->addStyleSheet(JURI::root() . 'media/com_easystaging/css/plans.css');

        // Then add JS to the document‚ - make sure all JS comes after CSS
        $jsFile = 'media/com_easystaging/js/plans.js';
        $document->addScript(JURI::root() . $jsFile);
        PlanHelper::loadJSLanguageKeys('/' . $jsFile);
    }
}
