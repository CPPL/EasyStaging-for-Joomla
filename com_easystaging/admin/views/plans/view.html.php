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
require_once JPATH_COMPONENT . '/helpers/plan.php';

/**
 * EasyStaging Manager View
 *
 */
class EasyStagingViewPlans extends JViewLegacy
{
    protected $items;

    protected $pagination;

    protected $state;

    protected $current_version;

    protected $jvtag;

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

        // Get our Joomla Tag, installed version and our canDo's
        $this->jvtag      = ES_General_Helper::getJoomlaVersionTag();

        // Get version
        $xml = simplexml_load_file(JPATH_BASE . '/components/com_easystaging/easystaging.xml');
        $this->current_version = (string) $xml->version;

        // Setup document (Toolbar, css, js etc)
        $this->addToolbar();
        $this->addCSSEtc();

        // Get data from the model
        $items = $this->get('Items');
        $pagination = $this->get('Pagination');

        if (count($errors = $this->get('Errors'))) {
            JError::raiseError(500, implode('<br />', $errors));

            return false;
        }
        // Assign data to the view
        $this->items = $items;
        $this->pagination = $pagination;

        parent::display($tpl);
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
