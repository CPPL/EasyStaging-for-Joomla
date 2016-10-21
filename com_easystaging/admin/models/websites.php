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
class EasyStagingModelWebsites extends JModelList
{
	public function __construct($config = array())
	{
		if (empty($config['filter_fields']))
		{
			$config['filter_fields'] = array(
				'site_name', 'site_name',
				'state', 'state',
                'type', 'type',
				'created_by', 'created_by',
                'modified_by', 'modified_by'
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

        $state = $this->getUserStateFromRequest($this->context . '.filter.state', 'filter_state', '');
        $this->setState('filter.state', $state);

        $type = $this->getUserStateFromRequest($this->context . '.filter.type', 'filter_type', '');
        $this->setState('filter.type', $type);

        $createdById = $app->getUserStateFromRequest($this->context . '.filter.created_by', 'filter_created_by');
		$this->setState('filter.created_by', $createdById);

        $modifiedById = $app->getUserStateFromRequest($this->context . '.filter.modified_by', 'filter_modified_by');
        $this->setState('filter.modified_by', $modifiedById);

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
			if ($item->checked_out)
			{
				$editor       = JFactory::getUser($item->checked_out);
				$item->editor = JText::sprintf('COM_EASYSTAGING_PLANS_CHECKED_OUT_BY_X_AKA_Y', $editor->username, $editor->name);
			}

			// Humanise Site type
			if ($item->type == 1)
			{
				$item->type = JText::_('COM_EASYSTAGING_LOCALSITE');
				$item->typeIcon = "screen";
			} elseif ($item->type == 2)
			{
				$item->type = JText::_('COM_EASYSTAGING_REMOTESITE');
				$item->typeIcon = "upload";
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
		$query->select($db->quoteName('site_name'));
        $query->select($db->quoteName('type'));
		$query->select($db->quoteName('state'));
		$query->select($db->quoteName('created_by'));
        $query->select($db->quoteName('created'));
		$query->select($db->quoteName('checked_out'));
		$query->select($db->quoteName('checked_out_time'));
		$query->select($db->quoteName('modified'));
		$query->select($db->quoteName('modified_by'));

        // From the EasyStaging table
		$query->from('#__easystaging_sites');

        // Filter by published state
        $state = $this->getState('filter.state');

        if (is_numeric($state))
        {
            $query->where('state = ' . (int) $state);
        }
        elseif ($state === '')
        {
            $query->where('(state = 0 OR state = 1)');
        }

        // Filter by Type
        $ftype = $this->getState('filter.type');

        if (is_numeric($ftype))
        {
            $type = $this->getState('filter.type.include', true) ? '= ' : '<>';
            $query->where('type ' . $type . (int) $ftype);
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

        // Filter by search in Plan name.
        $search = $this->getState('filter.search');

        if (!empty($search))
        {
			$search = $db->quote('%' . str_replace(' ', '%', $db->escape(trim($search), true) . '%'));
			$query->where('(site_name LIKE ' . $search . ' OR site_name LIKE ' . $search . ')');
        }

        // Add the list ordering clause.
        $orderCol =  $this->state->get('list.ordering',  'id');
        $orderDirn = $this->state->get('list.direction', 'desc');
        $query->order($db->escape($orderCol . ' ' . $orderDirn));

        return $query;
	}
}
