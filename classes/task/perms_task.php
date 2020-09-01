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

class perms_task extends \core\task\adhoc_task {

    public function execute() {
        global $CFG, $USER, $DB;
        require_once($CFG->dirroot.'/mod/panopto/lib.php');
        require_once($CFG->dirroot . "/repository/panopto/locallib.php");
        $data = $this->get_custom_data();

        $panopto = $data->panopto;

        // Instantiate Panopto client.
        $panoptoclient = new \repository_panopto_interface();
        // Set current user.
        $panoptoclient->set_authentication_info(
            get_config('panopto', 'instancename') . '\\' . $USER->username, '', get_config('panopto', 'applicationkey'));

        // Perform the call to Panopto API to obtain viewer url.
        $session = $panoptoclient->get_session_by_id($panopto->panoptosessionid, true);
        if (!$session) {
            throw new invalid_parameter_exception(get_string('errorsessionnotfound', 'repository_panopto'));
        }

        if (!$panopto->panoptogroupid) {
            // This should not happen really, but if module creation event has
            // not been processed for some reason, we will have no group,
            // so we need to create it and update instance record.
            $groupname = get_config('panopto', 'instancename') . '_cmid_' . $data->cmid;
            $group = $panoptoclient->create_external_group($groupname);

            // Update db record with Panopto group id.
            $panopto->panoptogroupid = $group->getId();
            $DB->update_record('panopto', $panopto);
        }

        // Grant access to the unique course module external group.
        $conditions = [
            'userid'         => $USER->id,
            'panoptogroupid' => $panopto->panoptogroupid
        ];
        if ($panoptoaccess = $DB->get_record('panopto_user_access', $conditions)) {
            // Access mapping exist, update access timestamp.
            $panoptoaccess->timeaccessed = time();
            $DB->update_record('panopto_user_access', $panoptoaccess);
        } else {
            // User needs to be added to the group and access mapping record needs to be created.
            $panoptouser = $panoptoclient->sync_current_user();
            $groupexternalid = get_config('panopto', 'instancename') . '_cmid_' . $data->cmid;
            $panoptoclient->add_member_to_external_group($groupexternalid, $panoptouser->getUserId());

            $panoptoaccess = new \stdClass();
            $panoptoaccess->userid = $USER->id;
            $panoptoaccess->panoptouserid = $panoptouser->getUserId();
            $panoptoaccess->panoptogroupid = $panopto->panoptogroupid;
            $panoptoaccess->panoptoextgroupid = $groupexternalid;
            $panoptoaccess->timeaccessed = time();
            $DB->insert_record('panopto_user_access', $panoptoaccess);
        }

        // Make sure that group is linked to session.
        $panoptoclient->grant_group_viewer_access_to_session($panopto->panoptogroupid, $panopto->panoptosessionid);

        // Perform the call to Panopto API to obtain authenticated url.
        $authurl = $panoptoclient->get_authenticated_url($session->getViewerUrl());

        // Save the url for the user to access.
        $panoptoauth = new \stdClass();
        $panoptoauth->userid = $USER->id;
        $panoptoauth->panoptosessionid = $panopto->panoptosessionid;
        $panoptoauth->panoptoauthurl = $authurl;
        $panoptoauth->validuntil = time() + 9;
        $DB->insert_record('panopto_auth_url', $panoptoauth);
    }
}