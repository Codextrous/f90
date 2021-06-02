<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  lib_f90
 *
 * @copyright   Copyright (C) 2015 - 2021 Function90. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace F90\Admin;

defined('_JEXEC') or die;

use F90\Interfaces\AdminListModelInterface;
use Joomla\CMS\MVC\Model\ListModel as JListModel;
use Joomla\Database\ParameterType;

/**
 * Methods supporting a list of fields records.
 *
 * @since  2.0
 */
abstract class ListModel extends JListModel implements AdminListModelInterface
{
    /**
     * Get Filter Fields from model class
     */
    abstract public function getFilterFields(): array;

    /**
     * Get state Fields from model class
     */
    abstract public function getStateFields(): array;

    /**
     * Get table name of current model instance
     */
    abstract public function getTableName(): string;

    /**
     * Constructor.
     *
     * @param   array  $config  An optional associative array of configuration settings.
     *
     */
    public function __construct($config = array())
    {
        if (empty($config['filter_fields'])) {
            $config['filter_fields'] = $this->getFilterFields();
        }

        parent::__construct($config);
    }

    /**
     * Method to auto-populate the model state.
     *
     * Note. Calling getState in this method will result in recursion.
     *
     * @param   string  $ordering   An optional ordering field.
     * @param   string  $direction  An optional direction (asc|desc).
     *
     * @return  void
     *
     */
    protected function populateState($ordering = 'a.id', $direction = 'asc')
    {
        if (!empty($stateFields = $this->getStateFields())) {
            foreach ($stateFields as $field) {
                $state = $this->getUserStateFromRequest($this->context . '.filter.' . $field, 'filter_' . $field);
                $this->setState('filter.' . $field, $state);
            }
        }

        // List state information.
        parent::populateState($ordering, $direction);
    }

    /**
     * Method to get a store id based on model configuration state.
     *
     * This is necessary because the model is used by the component and
     * different modules that might need different sets of data or different
     * ordering requirements.
     *
     * @param   string  $id  A prefix for the store id.
     *
     * @return  string  A store id.
     *
     */
    protected function getStoreId($id = '')
    {
        // Compile the store id.
        if (!empty($stateFields = $this->getStateFields())) {
            foreach ($stateFields as $field) {
                $id .= ':' . $this->getState('filter.' . $field);
            }
        }

        return parent::getStoreId($id);
    }

    /**
     * Build an SQL query to load the list data.
     *
     * @return  \Joomla\Database\DatabaseQuery
     *
     * @since   1.6
     */
    protected function getListQuery()
    {
        // Create a new query object.
        $db    = $this->getDbo();
        $query = $db->getQuery(true);

        // Select the required fields from the table.
        $query->select(
            $this->getState(
                'list.select',
                'a.*'
            )
        );
        $query->from($this->getTableName() . ' AS a');

        // Apply State
        if (!empty($stateFields = $this->getStateFields())) {
            foreach ($stateFields as $field) {
                $medthodName = 'applyState' . ucfirst($field);
                if (method_exists($this, $medthodName)) {
                    $this->$medthodName($db, $query);
                }
            }
        }

        // Add the list ordering clause.
        $orderCol  = $this->state->get('list.ordering', 'a.id');
        $orderDirn = $this->state->get('list.direction', 'ASC');

        $query->order($db->escape($orderCol) . ' ' . $db->escape($orderDirn));
        return $query;
    }

    /**
     * Method to get a list of fields.
     * Overridden to add a check for access levels.
     *
     * @return  mixed  An array of data items on success, false on failure.
     *
     * @since   4.0.0
     */
    public function getItems()
    {
        $items = parent::getItems();

        return $items;
    }

    protected function applyStatePublished($db, $query): void
    {
        // Filter by published state
        $published = (string) $this->getState('filter.published');

        if (is_numeric($published)) {
            $query->where($db->quoteName('a.published') . ' = :published');
            $query->bind(':published', $published, ParameterType::INTEGER);
        } elseif ($published === '') {
            $query->where('(' . $db->quoteName('a.published') . ' = 0 OR ' . $db->quoteName('a.published') . ' = 1)');
        }
    }
}
