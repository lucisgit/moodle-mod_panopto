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
 * Panopto course module event observer.
 *
 * @package    mod_panopto
 * @copyright  2017 Lancaster University (http://www.lancaster.ac.uk/)
 * @author     Ruslan Kabalin (https://github.com/kabalin)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_panopto\event;

/**
 * An event observer.
 * @copyright  2017 Lancaster University (http://www.lancaster.ac.uk/)
 * @author     Ruslan Kabalin (https://github.com/kabalin)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class observer {

    private static function get_repository_panopto_interface() {
        global $CFG;
        require_once($CFG->dirroot . "/repository/panopto/locallib.php");
        // Instantiate Panopto client.
        $panoptoclient = new \repository_panopto_interface();
        // Set authentication to Panopto admin.
        $panoptoclient->set_authentication_info(get_config('panopto', 'userkey'), get_config('panopto', 'password'));
        return $panoptoclient;
    }
    /**
     * Listen to events and make required Panopto API calls.
     * @param \core\event\course_module_created $event
     */
    public static function course_module_created(\core\event\course_module_created $event) {
        global $DB;
        if ($event->other['modulename'] === 'panopto') {
            $groupname = get_config('panopto', 'instancename') . '_cmid_' . $event->objectid;
            $panoptoclient = self::get_repository_panopto_interface();
            $group = $panoptoclient->create_external_group($groupname);

            // Update db record with Panopto group id.
            $data = $DB->get_record('panopto', array('id'=> $event->other['instanceid']), '*', MUST_EXIST);
            $data->panoptoextgroupid = $group->getId();
            $DB->update_record('panopto', $data);

            // Grant group access to the session.
            $panoptoclient->grant_group_viewer_access_to_session($group->getId(), $data->panoptosessionid);
        }
    }
}