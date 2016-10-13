<?php
/**
 * EasyStaging Model for EasyStaging Component
 * @link		http://seepeoplesoftware.com
 * @license		GNU/GPL
 * @copyright	Craig Phillips Pty Ltd
 */
 
// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();
 
jimport( 'joomla.application.component.modellist' );
 
/**
 * EasyStaging Model
 *
 */
class EasyStagingModelPlans extends JModelList
{
	public function __construct($config = array())
	{
		if (empty($config['filter_fields']))
		{
			$config['filter_fields'] = array(
				'name', 'name',
				'published', 'published',
				'created_by', 'created_by',
                'modified_by', 'modified_by',
				'publish_up', 'publish_up',
				'publish_down', 'publish_down',
				'published', 'published'
			);
		}

		parent::__construct($config);
	}

	protected function populateState($ordering = 'id', $direction = 'desc')
	{
		$app = JFactory::getApplication();

		// Adjust the context to support modal layouts.
		if ($layout = $app->input->get('layout'))
		{
			$this->context .= '.' . $layout;
		}

		$search = $this->getUserStateFromRequest($this->context . '.filter.search', 'filter_search');
		$this->setState('filter.search', $search);

        $published = $this->getUserStateFromRequest($this->context . '.filter.published', 'filter_published', '');
        $this->setState('filter.published', $published);

        $createdById = $app->getUserStateFromRequest($this->context . '.filter.created_by', 'filter_created_by');
		$this->setState('filter.created_by', $createdById);

        $modifiedById = $app->getUserStateFromRequest($this->context . '.filter.modified_by', 'filter_modified_by');
        $this->setState('filter.modified_by', $modifiedById);

        $lastRunById = $app->getUserStateFromRequest($this->context . '.filter.last_run_by', 'filter_last_run_by');
        $this->setState('filter.created', $lastRunById);

		// List state information.
		parent::populateState($ordering, $direction);
	}


	/**
	 * Method to get an array of data items.
	 *
	 * @return  mixed  An array of data items on success, false on failure.
	 *
	 * @since   11.1
	 */
	public function getItems()
	{
		// Let the parent do the heavy lifting
		$items = parent::getItems();

		foreach ($items as &$item)
		{
			$item->lastRunDTS  = JHtml::_('date.relative', $item->last_run);

            $lrb = $item->last_run_by;
            if (is_null($lrb) || $lrb == 0 || empty($lrb)) {
                $item->last_run_by = '';
            } else {
                $item->last_run_by = JFactory::getUser($item->last_run_by)->name;
            }

			if($item->checked_out)
			{
				$editor       = JFactory::getUser($item->checked_out);
				$item->editor = JText::sprintf('COM_EASYSTAGING_PLANS_CHECKED_OUT_BY_X_AKA_Y', $editor->username, $editor->name);
			}
		}

			// Return our items
		return $items;
	}

	/**
	 * Method to build an SQL query to load the list data.
	 *
	 * @return	string	An SQL query
	 */
	protected function getListQuery()
	{
		// Create a new query object.
		$db = JFactory::getDBO();
		$query = $db->getQuery(true);

		// Select some fields
		$query->select($db->quoteName('id'));
		$query->select($db->quoteName('name'));
		$query->select($db->quoteName('description'));
		$query->select($db->quoteName('published'));
		$query->select($db->quoteName('created_by'));
		$query->select($db->quoteName('publish_down'));
		$query->select($db->quoteName('publish_up'));
		$query->select($db->quoteName('checked_out'));
		$query->select($db->quoteName('checked_out_time'));
		$query->select($db->quoteName('modified'));
		$query->select($db->quoteName('modified_by'));
		$query->select($db->quoteName('last_run'));
        $query->select($db->quoteName('last_run_by'));

        // From the EasyStaging table
		$query->from('#__easystaging_plans');

        // Filter by published state
        $published = $this->getState('filter.published');

        if (is_numeric($published))
        {
            $query->where('published = ' . (int) $published);
        }
        elseif ($published === '')
        {
            $query->where('(published = 0 OR published = 1)');
        }

        // Filter by Created by
        $creatorId = $this->getState('filter.created_by');

        if (is_numeric($creatorId))
        {
            $type = $this->getState('filter.created_by.include', true) ? '= ' : '<>';
            $query->where('created_by ' . $type . (int) $creatorId);
        }

        // Filter by Modified by
        $modifiedById = $this->getState('filter.modified_by');

        if (is_numeric($modifiedById))
        {
            $type = $this->getState('filter.modified_by.include', true) ? '= ' : '<>';
            $query->where('modified_by' . $type . (int) $modifiedById);
        }

        // Filter by Last Run by
        $lastRunById = $this->getState('filter.last_run_by');

        if (is_numeric($lastRunById))
        {
            $type = $this->getState('filter.last_run_by.include', true) ? '= ' : '<>';
            $query->where('last_run_by ' . $type . (int) $lastRunById);
        }

        // Filter by search in Plan name.
        $search = $this->getState('filter.search');

        if (!empty($search))
        {
            if (stripos($search, 'id:') === 0)
            {
                $query->where('id = ' . (int) substr($search, 3));
            }
            else
            {
                $search = $db->quote('%' . str_replace(' ', '%', $db->escape(trim($search), true) . '%'));
                $query->where('(name LIKE ' . $search . ' OR name LIKE ' . $search . ')');
            }
        }

        // Add the list ordering clause.
        $orderCol = $this->state->get('list.ordering', 'id');
        $orderDirn = $this->state->get('list.direction', 'desc');
        $query->order($db->escape($orderCol . ' ' . $orderDirn));

        return $query;
	}
}
