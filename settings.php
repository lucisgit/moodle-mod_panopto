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
 * Panopto course module settings.
 *
 * @package    mod_panopto
 * @copyright  2017 Lancaster University (http://www.lancaster.ac.uk/)
 * @author     Ruslan Kabalin (https://github.com/kabalin)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {
    require_once($CFG->libdir . '/resourcelib.php');

    $url = new moodle_url('/admin/repository.php', array('repos' => 'panopto', 'action' => 'edit', 'sesskey' => sesskey()));
    $notice = html_writer::div(get_string('usereposettings', 'panopto', $url->out()), 'warning form-item');
    $settings->add(new admin_setting_heading('panoptomodsettingslink', '', $notice));

    $name = new lang_string('requiredaccesstime', 'mod_panopto');
    $options = array(
        -1 => new lang_string('unlimited', 'mod_panopto'),
        1 => new lang_string('numhours', '', 1),
        2 => new lang_string('numhours', '', 2),
        6 => new lang_string('numhours', '', 6),
        12 => new lang_string('numhours', '', 12),
        24 => new lang_string('numhours', '', 24),
    );
    $description = new lang_string('requiredaccesstime_desc', 'mod_panopto');
    $settings->add(new admin_setting_configselect('panopto/requiredaccesstime',
                                                    $name,
                                                    $description,
                                                    1,
                                                    $options));

    $name = new lang_string('asynchronousmode', 'mod_panopto');
    $description = new lang_string('asynchronousmode_desc', 'mod_panopto');
    $options = [
        0 => new lang_string('off', 'mod_panopto'),
        1 => new lang_string('on', 'mod_panopto')
    ];
    $settings->add(new admin_setting_configselect('panopto/asynchronousmode', $name, $description, 0, $options));
}