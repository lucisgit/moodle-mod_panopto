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
 * Panopto course module library.
 *
 * @package    mod_panopto
 * @copyright  2017 Lancaster University (http://www.lancaster.ac.uk/)
 * @author     Ruslan Kabalin (https://github.com/kabalin)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot . "/repository/panopto/locallib.php");

/**
 * List of features supported in Panopto module
 * @param string $feature FEATURE_xx constant for requested feature
 * @return mixed True if module supports feature, false if not, null if doesn't know
 */
function panopto_supports($feature) {
    switch($feature) {
        case FEATURE_MOD_ARCHETYPE:
            return MOD_ARCHETYPE_RESOURCE;
        case FEATURE_GROUPS:
            return false;
        case FEATURE_GROUPINGS:
            return false;
        case FEATURE_MOD_INTRO:
            return true;
        case FEATURE_COMPLETION_TRACKS_VIEWS:
            return true;
        case FEATURE_GRADE_HAS_GRADE:
            return false;
        case FEATURE_GRADE_OUTCOMES:
            return false;
        case FEATURE_BACKUP_MOODLE2:
            return true;
        case FEATURE_SHOW_DESCRIPTION:
            return true;
        default:
            return null;
    }
}

/**
 * Returns all other caps used in module
 * @return array
 */
function panopto_get_extra_capabilities() {
    return array('moodle/site:accessallgroups');
}

/**
 * This function is used by the reset_course_userdata function in moodlelib.
 * @param $data the data submitted from the reset course.
 * @return array status array
 */
function panopto_reset_userdata($data) {
    return array();
}

/**
 * List of view style log actions
 * @return array
 */
function panopto_get_view_actions() {
    return array('view', 'view all');
}

/**
 * List of update style log actions
 * @return array
 */
function panopto_get_post_actions() {
    return array('update', 'add');
}

/**
 * Add Panopto instance.
 *
 * @param object $data
 * @param object $mform
 * @return int new panopto instance id
 */
function panopto_add_instance($data, $mform) {
    global $DB;

    $data->timemodified = time();
    $data->id = $DB->insert_record('panopto', $data);

    // See \mod_panopto\event\observer::course_module_created for calls
    // made after instance has been created.

    return $data->id;
}

/**
 * Callback function for Panopto instance create event.
 *
 * This effectively a call to Panopto API for creating the unique external group
 * for this coursemodule and add it to recorded session we wish to use in this activity.
 *
 * @param int $cmid coursemodule id
 * @param int $instanceid id of the record in mod_panopto table.
 * @return bool true
 */
function panopto_instance_created_callback($cmid, $instanceid) {
    global $DB, $CFG;

    // Get existing instance record.
    if (!$data = $DB->get_record('panopto', array('id' => $instanceid))) {
        return false;
    }

    // Instantiate Panopto client.
    require_once($CFG->dirroot . "/repository/panopto/locallib.php");
    $panoptoclient = new \repository_panopto_interface();
    // Create unique external group for this course module.
    $groupname = get_config('panopto', 'instancename') . '_cmid_' . $cmid;
    $group = $panoptoclient->create_external_group($groupname);

    // Update db record with Panopto group id.
    $data->panoptogroupid = $group->getId();
    $DB->update_record('panopto', $data);

    // Grant group access to the session we wish to use in this coursemodule.
    $panoptoclient->grant_group_viewer_access_to_session($group->getId(), $data->panoptosessionid);

    return true;
}

/**
 * Update Panopto instance.
 *
 * @param object $data
 * @param object $mform
 * @return bool true
 */
function panopto_update_instance($data, $mform) {
    global $DB, $CFG;

    // Get existing instance record.
    if (!$panopto = $DB->get_record('panopto', array('id' => $data->instance))) {
        return false;
    }

    // TODO: Create a special event type for this API call and its proper logging.
    // If session has been changed, move this course module external group to the new session in Panopto.
    if ($panopto->panoptosessionid !== $data->panoptosessionid) {
        // Instantiate Panopto client.
        require_once($CFG->dirroot . "/repository/panopto/locallib.php");
        $panoptoclient = new \repository_panopto_interface();
        if ($panopto->panoptogroupid) {
            // Revoke group access from the previous session.
            $panoptoclient->revoke_group_viewer_access_from_session($panopto->panoptogroupid, $panopto->panoptosessionid);
        } else {
            // This should not happen really, but if module creation event has
            // not been processed for some reason, we will have no group,
            // so we need to create it and update instance record.
            $cm = get_coursemodule_from_instance('panopto', $panopto->id);
            $groupname = get_config('panopto', 'instancename') . '_cmid_' . $cm->id;
            $group = $panoptoclient->create_external_group($groupname);
            $panopto->panoptogroupid = $group->getId();
        }
        // Grant group access to the new session.
        $panoptoclient->grant_group_viewer_access_to_session($panopto->panoptogroupid, $data->panoptosessionid);
        $panopto->panoptosessionid = $data->panoptosessionid;
    }

    // Update instance record.
    $panopto->name = $data->name;
    $panopto->intro = $data->intro;
    $panopto->introformat = $data->introformat;
    $panopto->timemodified = time();
    $DB->update_record('panopto', $panopto);

    return true;
}

/**
 * Delete Panopto instance.
 *
 * @param int $id
 * @return bool true
 */
function panopto_delete_instance($id) {
    global $DB, $CFG;

    // Get existing instance record.
    if (!$panopto = $DB->get_record('panopto', array('id' => $id))) {
        return false;
    }

    // TODO: Create a special event type for this API call and its proper logging.
    // If groupid defined, remove external group and user access map records.
    if ($panopto->panoptogroupid) {
        // Instantiate Panopto client.
        require_once($CFG->dirroot . "/repository/panopto/locallib.php");
        $panoptoclient = new \repository_panopto_interface();
        // Delete course module external group and access mapping.
        $panoptoclient->delete_group($panopto->panoptogroupid);
        $DB->delete_records('panopto_user_access', array('panoptogroupid' => $panopto->panoptogroupid));
    }
    // Delete instance record.
    $DB->delete_records('panopto', array('id' => $panopto->id));

    return true;
}
