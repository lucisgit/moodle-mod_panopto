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
 * Panopto course permission tasks.
 *
 * Just a wrapped version of the original logic.
 *
 * @package    mod_panopto
 * @copyright  2020 Lancaster University (http://www.lancaster.ac.uk/)
 * @author     Chris Lingwood
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_panopto\task;

defined('MOODLE_INTERNAL') || die();

/**
 * Panopto remote permissions adhoc task.
 *
 * @package    mod_panopto
 * @copyright  2020 Lancaster University (http://www.lancaster.ac.uk/)
 * @author     Chris Lingwood
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class perms_task extends \core\task\adhoc_task {

    /**
     * Run an adhoc task to set up remote permissions.
     */
    public function execute() {
        global $CFG, $USER, $DB;

        require_once($CFG->dirroot . '/mod/panopto/locallib.php');

        $data = $this->get_custom_data();
        $panopto = $data->panopto;

        // Set up remote permissions and get authenticated url.
        $authurl = setup_remote_permissions($data->cmid, $panopto);

        // Save the url for the user to access.
        $panoptoauth = new \stdClass();
        $panoptoauth->userid = $USER->id;
        $panoptoauth->panoptosessionid = $panopto->panoptosessionid;
        $panoptoauth->panoptoauthurl = $authurl;
        $panoptoauth->validuntil = time() + 9;
        $DB->insert_record('panopto_auth_url', $panoptoauth);
    }
}
