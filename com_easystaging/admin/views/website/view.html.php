<?php
/**
 * Plan Editor View for EasyStaging Component
 *
 * @link        http://seepeoplesoftware.com
 * @license     GNU/GPL
 * @copyright   Craig Phillips Pty Ltd
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

require_once JPATH_COMPONENT . '/helpers/general.php';
require_once JPATH_COMPONENT . '/helpers/plan.php';

/**
 * EasyStaging Plan Editor View
 *
 * @property mixed form
 */
class EasyStagingViewWebsite extends JViewLegacy
{
    /* @var $form JForm */
    protected $form;

    protected $item;

    protected $canDo;

    protected $runOnly;

    /**
     * Execute and display a template script.
     *
     * @param   string  $tpl  The name of the template file to parse; automatically searches through the template paths.
     *
     * @return  mixed  A string if successful, otherwise a JError object.
     *
     * @since   1.0
     */
    public function display($tpl = null)
    {
        // Check EasyStaging is configured properly
        if (!ES_General_Helper::isEveryThingOK()) {
            JFactory::getApplication()->redirect('index.php?option=com_easystaging', 303);
            return false;
        }

        // Get the Data
        $this->form = $this->get('Form');
        $this->item = $this->get('Item');

        // Check for errors.
        if (count($errors = $this->get('Errors'))) {
            JError::raiseError(500, implode('<br />', $errors));

            return false;
        }

        // Should we be here?
        $this->canDo = PlanHelper::getActions($this->item->id);

        // Set the toolbar etc
        $this->addToolBar();
        $this->addCSSEtc();

        // Display the template
        parent::display($tpl);
    }

    /**
     * Add the Toolbar for Plan view.
     *
     * @return void
     */
    private function addToolbar()
    {
        $jinput = JFactory::getApplication()->input;
        $jinput->set('hidemainmenu', true);
        $canDo      = $this->canDo;
        $user       = JFactory::getUser();

        $isNew      = ($this->item->id == 0);
        $checkedOut = !($this->item->checked_out == 0 || $this->item->checked_out == $user->get('id'));

        if ($canDo->get('core.edit') || $canDo->get('core.create')) {
            JToolBarHelper::title(
                $isNew ? JText::_('COM_EASYSTAGING_MANAGER_WEBSITE_NEW') : JText::_('COM_EASYSTAGING_MANAGER_WEBSITE_EDIT'),
                'easystaging'
            );
            JToolBarHelper::apply('website.apply');
            JToolBarHelper::save('website.save');
        }

        if (!$checkedOut && ($canDo->get('core.create'))) {
            JToolBarHelper::save2new('website.save2new');
        }

        JToolBarHelper::cancel('website.cancel', $isNew ? 'JTOOLBAR_CANCEL' : 'JTOOLBAR_CLOSE');
        JToolBarHelper::divider();
        JToolBarHelper::help(
            'COM_EASYSTAGING_HELP_EASYSTAGING_MANAGER',
            false,
            'https://seepeoplesoftware.com/products/easystaging/2.0/help/website.html'
        );
    }

    /**
     * Add the CSS for Plan view.
     *
     * @return void
     */
    private function addCSSEtc()
    {
        // Get the document object
        $document = JFactory::getDocument();

        // First add CSS to the document
        $document->addStyleSheet(JURI::root() . 'media/com_easystaging/css/plan.css');

        // Load the defaults first so that our script loads after them
        JHtml::_('behavior.framework', true);
        JHtml::_('behavior.tooltip');
        JHtml::_('behavior.multiselect');

        // Then add JS to the document‚ - make sure all JS comes after CSS
        // General Tools first
        $jsFile = 'media/com_easystaging/js/atools.js';
        $document->addScript(JURI::root() . $jsFile);
        PlanHelper::loadJSLanguageKeys('/' . $jsFile);

        // Load website specific JS
        $jsFile = 'media/com_easystaging/js/website.js';
        $document->addScript(JURI::root() . $jsFile);
        PlanHelper::loadJSLanguageKeys('/' . $jsFile);
    }

}
