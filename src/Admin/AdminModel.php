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

use F90\Interfaces\AdminModelInterface;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\FormFactoryInterface;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\CMS\MVC\Model\AdminModel as JAdminModel;
use Joomla\Registry\Registry;

/**
 * Methods supporting a list of fields records.
 *
 * @since  2.0
 */
abstract class AdminModel extends JAdminModel implements AdminModelInterface
{
	/**
	 * List of fields to cast
	 *
	 * @var array
	 */
	protected $cast = [];

    /**
	 * Constructor.
	 *
	 * @param   array                 $config       An array of configuration options (name, state, dbo, table_path, ignore_request).
	 * @param   MVCFactoryInterface   $factory      The factory.
	 * @param   FormFactoryInterface  $formFactory  The form factory.
	 *
	 * @since   1.6
	 * @throws  \Exception
	 */
	public function __construct($config = array(), MVCFactoryInterface $factory = null, FormFactoryInterface $formFactory = null)
	{
        $this->text_prefix = strtoupper('com_'.$this->getComponentName());

		parent::__construct($config, $factory, $formFactory);
	}
	
	/**
	 * Cast to Array
	 *
	 * @param string $value
	 * @return array
	 */
	protected function castToArray(string $value) : array
	{
		// Convert the params field to an array.
		$registry = new Registry($value);
		return $registry->toArray();
	}

	/**
	 * Method to get a single record.
	 *
	 * @param   integer  $pk  The id of the primary key.
	 *
	 * @return  mixed  Object on success, false on failure.
	 */
	public function getItem($pk = null)
	{
		if ($item = parent::getItem($pk)) {
			foreach ($this->cast as $column => $castTo) {
				if (isset($item->{$column})) {
					$castFunctionName = 'castTo'.ucfirst($castTo);
					$item->{$column} = $this->$castFunctionName($item->{$column});
				}
			}
		}

		return $item;
	}

	/**
	 * Method to get the record form.
	 *
	 * @param   array    $data      Data for the form.
	 * @param   boolean  $loadData  True if the form is to load its own data (default case), false if not.
	 *
	 * @return  Form|boolean  A Form object on success, false on failure
	 *
	 * @since   1.6
	 */
	public function getForm($data = array(), $loadData = true)
	{
		// Get the form.
		$form = $this->loadForm($this->typeAlias, $this->getName(), array('control' => 'jform', 'load_data' => $loadData));

		if (empty($form)) {
			return false;
		}

		return $form;
	}

	/**
	 * Method to save the form data.
	 *
	 * @param   array  $data  The form data.
	 *
	 * @return  boolean  True on success.
	 *
	 * @since   1.6
	 */
	public function save($data)
	{
		$filter = \JFilterInput::getInstance();
	
		foreach ($this->clean as $column => $cleanBy) { 
			if (isset($data[$column]) && isset($data[$column]))	{
				$data[$column] = $filter->clean($data[$column], $cleanBy);
			}
		}

		foreach ($this->cast as $column => $castTo) {
			if ($castTo === 'array' && isset($data[$column])) {
				$registry = new Registry($data[$column]);
				$data[$column] = (string) $registry;
			}
		}

		return parent::save($data);
	}

	/**
	 * Method to get the data that should be injected in the form.
	 *
	 * @return  mixed  The data for the form.
	 *
	 * @since   1.6
	 */
	protected function loadFormData()
	{
		// Check the session for previously entered form data.
		$app = Factory::getApplication();
		$data = $app->getUserState('com_'.$this->getComponentName().'.edit.'.$this->getName().'.data', array());

		if (empty($data)) {
			$data = $this->getItem();

			// pre select soem values can be done here
		}

		foreach ($this->cast as $column => $castTo) {
			if ($castTo === 'array' && isset($data->$column) && $data->$column instanceof Registry) {
				$data->$column = $data->$column->toArray();
			}
		}

		$this->preprocessData($this->typeAlias, $data);

		return $data;
	}

	/**
	 * Method to test whether a record can be deleted.
	 *
	 * @param   object  $record  A record object.
	 *
	 * @return  boolean  True if allowed to delete the record. Defaults to the permission set in the component.
	 *
	 * @since   1.6
	 */
	protected function canDelete($record)
	{
		if (empty($record->id) || ($record->state != -2 && !Factory::getApplication()->isClient('api')))
		{
			return false;
		}

		return Factory::getUser()->authorise('core.delete', $this->typeAlias.'.' . (int) $record->id);
	}

	/**
	 * Method to test whether a record can have its state edited.
	 *
	 * @param   object  $record  A record object.
	 *
	 * @return  boolean  True if allowed to change the state of the record. Defaults to the permission set in the component.
	 *
	 * @since   1.6
	 */
	protected function canEditState($record)
	{
		$user = Factory::getUser();

		// Check for existing article.
		if (!empty($record->id))
		{
			return $user->authorise('core.edit.state', $this->typeAlias.'.' . (int) $record->id);
		}

		// Default to component settings if neither article nor category known.
		return parent::canEditState($record);
	}
}
