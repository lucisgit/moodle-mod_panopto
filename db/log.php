<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Panopto course module definition of log events.
 *
 * @package    mod_panopto
 * @copyright  2017 Lancaster University (http://www.lancaster.ac.uk/)
 * @author     Ruslan Kabalin (https://github.com/kabalin)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$logs = [
    ['module' => 'panopto', 'action' => 'view', 'mtable' => 'panopto', 'field' => 'name'],
    ['module' => 'panopto', 'action' => 'view all', 'mtable' => 'panopto', 'field' => 'name'],
    ['module' => 'panopto', 'action' => 'update', 'mtable' => 'panopto', 'field' => 'name'],
    ['module' => 'panopto', 'action' => 'add', 'mtable' => 'panopto', 'field' => 'name'],
];
