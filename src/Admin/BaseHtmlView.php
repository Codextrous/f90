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
use Joomla\CMS\Helper\ContentHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\GenericDataException;
use Joomla\CMS\MVC\View\HtmlView as JBaseHtmlView;
use Joomla\CMS\Toolbar\Toolbar;
use Joomla\CMS\Toolbar\ToolbarHelper;

/**
 * View class for a list of fields.
 *
 * @since  2.0
 */
abstract class BaseHtmlView extends JBaseHtmlView implements AdminHtmlViewInterface
{
    /**
     * An array of items
     *
     * @var  array
     */
    protected $items;

    /**
     * The pagination object
     *
     * @var  \JPagination
     */
    protected $pagination;

    /**
     * The model state
     *
     * @var  \JObject
     */
    protected $state;

    /**
     * Form object for search filters
     *
     * @var  \JForm
     */
    public $filterForm;

    /**
     * The active search filters
     *
     * @var  array
     */
    public $activeFilters;

    /**
     * Set Page Title
     */
    public function getContext()
    {
        return 'COM_' . strtoupper($this->getComponentName()) . '_' . strtoupper($this->getName());
    }

    /**
     * Display the view.
     *
     * @param   string  $tpl  The name of the template file to parse; automatically searches through the template paths.
     *
     * @return  mixed  A string if successful, otherwise an Error object.
     */
    public function display($tpl = null)
    {
        $this->items         = $this->get('Items');
        $this->pagination    = $this->get('Pagination');
        $this->state         = $this->get('State');
        $this->filterForm    = $this->get('FilterForm');
        $this->activeFilters = $this->get('ActiveFilters');

        // Check for errors.
        if (count($errors = $this->get('Errors'))) {
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
     * @since   1.6
     */
    protected function addToolbar()
    {
        // Get the toolbar object instance
        $toolbar = Toolbar::getInstance('toolbar');

        ToolbarHelper::title(Text::_($this->getContext() . '_PAGE_TITLE'), $this->getComponentName());

        $canDo = ContentHelper::getActions('com_' . $this->getComponentName());

        if ($canDo->get('core.create')) {
            $toolbar->addNew('field.add');
        }

        if ($canDo->get('core.edit.state')) {
            $dropdown = $toolbar->dropdownButton('status-group')
                ->text('JTOOLBAR_CHANGE_STATUS')
                ->toggleSplit(false)
                ->icon('fa fa-globe')
                ->buttonClass('btn btn-info')
                ->listCheck(true);

            $childBar = $dropdown->getChildToolbar();

            $childBar->publish('fields.publish')->listCheck(true);

            $childBar->unpublish('fields.unpublish')->listCheck(true);

            if ($this->state->get('filter.published') != -2) {
                $childBar->trash('fields.trash')->listCheck(true);
            }
        }

        if ($this->state->get('filter.published') == -2 && $canDo->get('core.delete')) {
            $toolbar->delete('fields.delete')
                ->text('JTOOLBAR_EMPTY_TRASH')
                ->message('JGLOBAL_CONFIRM_DELETE')
                ->listCheck(true);
        }
    }
}
