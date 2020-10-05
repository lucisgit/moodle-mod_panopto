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
 * Local function library for Panopto module.
 *
 * @package     mod_panopto
 * @copyright   2020 Tony Butler <a.butler4@lancaster.ac.uk>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/panopto/classes/task/perms_task.php');
require_once($CFG->dirroot . '/repository/panopto/locallib.php');

/**
 * Sets up remote permissions for a user on Panopto and returns an authenticated url.
 *
 * @param int $cmid Course module id
 * @param stdClass $panopto Panopto module object
 * @return string Auth url for the user
 */
function setup_remote_permissions($cmid, $panopto) {
    global $DB, $USER;

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
        $groupname = get_config('panopto', 'instancename') . '_cmid_' . $cmid;
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
        $groupexternalid = get_config('panopto', 'instancename') . '_cmid_' . $cmid;
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
    return $panoptoclient->get_authenticated_url($session->getViewerUrl());
}

/**
 * Get an auth url which has not expired.
 *
 * @param stdClass $panopto
 * @return bool|stdClass returns an auth url or false
 */
function get_valid_auth_url($panopto) {
    global $DB, $USER;

    // Get the auth url.
    $select = "userid = :userid AND panoptosessionid = :sessionid AND validuntil > :now";
    $params = [
        'userid'    => $USER->id,
        'sessionid' => $panopto->panoptosessionid,
        'now'       => time() + 1
    ];

    return $DB->get_record_select('panopto_auth_url', $select, $params, 'id, panoptoauthurl, validuntil');
}

/**
 * Check for a scheduled task to request an auth url.
 *
 * @param stdClass $panopto
 * @return bool|stdClass id of scheduled task or false
 */
function get_scheduled_task($panopto) {
    global $DB, $USER;

    // Check if we are scheduled already.
    $select = "userid = :userid AND classname = :classname AND " . $DB->sql_like('customdata', ':sessionid');
    $params = [
        'userid'    => $USER->id,
        'classname' => '\mod_panopto\task\perms_task',
        'sessionid' => '%"panoptosessionid":"' . $panopto->panoptosessionid . '"%'
    ];

    return $DB->get_record_select('task_adhoc', $select, $params, 'id');
}

/**
 * Schedule the request of an auth url using an adhoc task.
 *
 * @param int $cmid
 * @param stdClass $panopto
 */
function schedule_task($cmid, $panopto) {
    global $USER;

    // Schedule the perms tasks.
    $task = new mod_panopto\task\perms_task();
    $task->set_userid($USER->id);
    $task->set_custom_data([
        'panopto' => $panopto,
        'cmid'    => $cmid,
    ]);

    \core\task\manager::queue_adhoc_task($task);
}
