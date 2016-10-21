<?php
/**
 * EasyStaging Model for EasyStaging Component
 * @link        http://seepeoplesoftware.com
 * @license     GNU/GPL
 * @copyright   Craig Phillips Pty Ltd
 */
 
// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();
// import Joomla modelform library
jimport('joomla.application.component.modeladmin');
 
/**
 * EasyStaging Plan Model
 *
 */
class EasyStagingModelWebsite extends JModelAdmin
{
    /**
     * Method override to check if you can edit an existing record.
     *
     * @param    array   $data   An array of input data.
     * @param    string  $key    The name of the key for the primary key.
     *
     * @return null
     */
    protected function allowEdit($data = array(), $key = 'id')
    {
        // Check specific edit permission then general edit permission.
        return JFactory::getUser()->authorise('core.edit', 'com_easystaging.plan.'.
        ((int) isset($data[$key]) ? $data[$key] : 0))
        or parent::allowEdit($data, $key);
    }

    /**
     * Returns a reference to the a Table object, always creating it.
     *
     * @param   type    The table type to instantiate
     * @param   string  A prefix for the table class name. Optional.
     * @param   array   Configuration array for model. Optional.
     * @return  JTable  A database object
     * @since   1.6
     */
    public function getTable($type = 'Site', $prefix = 'EasyStagingTable', $config = array())
    {
        return JTable::getInstance($type, $prefix, $config);
    }
    /**
     * Method to get the record form.
     *
     * @param   array   $data       Data for the form.
     * @param   boolean $loadData   True if the form is to load its own data (default case), false if not.
     * @return  mixed   A JForm object on success, false on failure
     * @since   1.6
     */
    public function getForm($data = array(), $loadData = true)
    {
        // Get the form.
        $form = $this->loadForm('com_easystaging.website', 'website', array('control' => 'jform', 'load_data' => $loadData));
        if (empty($form)) {
            return false;
        }
        return $form;
    }

    /**
     * Method to get the data that should be injected in the form.
     *
     * @return  mixed   The data for the form.
     * @since   1.6
     */
    protected function loadFormData()
    {
        // Check the session for previously entered form data.
        $data = JFactory::getApplication()->getUserState('com_easystaging.edit.website.data', array());
        if (empty($data)) {
            $data = $this->getItem();
        }
        return $data;
    }

    /**
     * Method to get a single record.
     *
     * @param   integer The id of the primary key.
     *
     * @return  mixed   Object on success, false on failure.
     */
    public function getItem($pk = null)
    {
        if ($item = parent::getItem($pk)) {
            // @todo Do some work here once we have an item…
        } else {
            // @todo Warn about not finding the item
        }

        return $item;
    }

    /*
     * Over-ride the save so we can store the additional tables at the same time.
     * 
     * @param boolean true on success or false on failure
     * 
     */
    public function save($data)
    {
        if ($result = parent::save($data)) {
            // Check to see if it's a new Website (for new 2.0 features)
            if ($this->getState('website.new', 1)) {   // Update the 'id' in $data to the newly saved Website's id.
                $data['id'] = $this->getState('website.id', 0);
            }
        }
        return $result;
    }
}
