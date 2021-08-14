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

use F90\Interfaces\AdminFormControllerInterface;
use Joomla\CMS\MVC\Controller\FormController as JFormController;

/**
 * Methods supporting a list of fields records.
 *
 * @since  2.0
 */
abstract class FormController extends JFormController implements AdminFormControllerInterface
{

}