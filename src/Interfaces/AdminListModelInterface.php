<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  lib_f90
 *
 * @copyright   Copyright (C) 2015 - 2021 Function90. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace F90\Interfaces;

defined('_JEXEC') or die;

/**
 * Interfce for Admin List model
 */
interface AdminListModelInterface
{
    public function getComponentName(): string;
}