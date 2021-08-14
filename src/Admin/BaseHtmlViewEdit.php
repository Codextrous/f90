<?php

/**
 * @package     Joomla.Library
 * @subpackage  lib_f90
 *
 * @copyright   Copyright (C) 2015 - 2021 Function90. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace F90\Admin;

defined('_JEXEC') or die;

use F90\Interfaces\AdminHtmlViewInterface;
use Joomla\CMS\MVC\View\HtmlView as JBaseHtmlView;
use Joomla\CMS\Factory;
use Joomla\CMS\Helper\ContentHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\GenericDataException;
use Joomla\CMS\Toolbar\Toolbar;
use Joomla\CMS\Toolbar\ToolbarHelper;

/**
 * View class for a list of fields.
 *
 * @since  2.0
 */
abstract class BaseHtmlViewEdit extends JBaseHtmlView  implements AdminHtmlViewInterface
{
    /**
	 * The \JForm object
	 *
	 * @var \Joomla\CMS\Form\Form
	 */
    protected $form;
    
    /**
	 * The active item
	 *
	 * @var  object
	 */
	protected $item;

	/**
	 * The model state
	 *
	 * @var  object
	 */
    protected $state;
    
    /**
	 * The actions the user is authorised to perform
	 *
	 * @var  \JObject
	 */
    protected $canDo;
    
    /**
	 * Execute and display a template script.
	 *
	 * @param   string  $tpl  The name of the template file to parse; automatically searches through the template paths.
	 *
	 * @return  mixed  A string if successful, otherwise an Error object.
	 *
	 * @throws \Exception
	 * @since   2.0
	 */
	public function display($tpl = null)
	{
		$this->form  = $this->get('Form');
		$this->item  = $this->get('Item');
		$this->state = $this->get('State');
		$this->canDo = ContentHelper::getActions('com_' . $this->getComponentName(), $this->getName(), $this->item->id);

		// Check for errors.
		if (count($errors = $this->get('Errors')))
		{
			throw new GenericDataException(implode("\n", $errors), 500);
		}

		$this->addToolbar();

		return parent::display($tpl);
    }
    
    /**
	 * Add the page title and toolbar.
	 *
	 * @return  void
	 *
	 * @throws \Exception
	 * @since   1.6
	 */
	protected function addToolbar()
	{
        $componentName = 'com_' . $this->getComponentName();
        $name = $this->getName();

		Factory::getApplication()->input->set('hidemainmenu', true);
		$user       = Factory::getUser();
		$userId     = $user->id;
		$isNew      = ($this->item->id == 0);
		$checkedOut = !(is_null($this->item->checked_out) || $this->item->checked_out == $userId);

		// Built the actions for new and existing records.
		$canDo = $this->canDo;

		$toolbar = Toolbar::getInstance();

        $nameText = strtoupper($name);
		ToolbarHelper::title(
			Text::_(strtoupper($componentName) . '_PAGE_' . ($checkedOut ? 'VIEW_' . $nameText : ($isNew ? 'ADD_' . $nameText : 'EDIT_' . $nameText))),
			'pencil-2 ' . $name . '-add'
		);

		// For new records, check the create permission.
		if ($isNew && (count($user->getAuthorisedCategories($componentName, 'core.create')) > 0))
		{
			$toolbar->apply('article.apply');

			$saveGroup = $toolbar->dropdownButton('save-group');

			$saveGroup->configure(
				function (Toolbar $childBar) use ($user, $name)
				{
					$childBar->save($name.'.save');

					if ($user->authorise('core.create', 'com_menus.menu'))
					{
						$childBar->save($name.'.save2menu', Text::_('JTOOLBAR_SAVE_TO_MENU'));
					}

					$childBar->save2new($name.'.save2new');
				}
			);

			$toolbar->cancel($name.'.cancel', 'JTOOLBAR_CLOSE');
		}
		else
		{
			// Since it's an existing record, check the edit permission, or fall back to edit own if the owner.
			$itemEditable = $canDo->get('core.edit') || ($canDo->get('core.edit.own') && $this->item->created_by == $userId);

			if (!$checkedOut && $itemEditable)
			{
				$toolbar->apply($name.'.apply');
			}

			$saveGroup = $toolbar->dropdownButton('save-group');

			$saveGroup->configure(
				function (Toolbar $childBar) use ($checkedOut, $itemEditable, $canDo, $user, $name)
				{
					// Can't save the record if it's checked out and editable
					if (!$checkedOut && $itemEditable)
					{
						$childBar->save($name . '.save');

						// We can save this record, but check the create permission to see if we can return to make a new one.
						if ($canDo->get('core.create'))
						{
							$childBar->save2new($name . '.save2new');
						}
					}

					// If checked out, we can still save
					if ($canDo->get('core.create'))
					{
						$childBar->save2copy($name . '.save2copy');
					}
				}
			);

			$toolbar->cancel($name . '.cancel', 'JTOOLBAR_CLOSE');
		}

		$toolbar->divider();
		$toolbar->help('JHELP_CONTENT_ARTICLE_MANAGER_EDIT');
	}
}
