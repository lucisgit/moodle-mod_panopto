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
 * Panopto course module plugin.
 *
 * @package    mod_panopto
 * @copyright  2017 Lancaster University (http://www.lancaster.ac.uk/)
 * @author     Ruslan Kabalin (https://github.com/kabalin)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');
require_once($CFG->dirroot.'/mod/panopto/lib.php');
require_once($CFG->libdir . '/completionlib.php');
require_once($CFG->dirroot . "/repository/panopto/locallib.php");

$id = required_param('id', PARAM_INT);
list ($course, $cm) = get_course_and_cm_from_cmid($id, 'panopto');
$panopto = $DB->get_record('panopto', array('id'=> $cm->instance), '*', MUST_EXIST);

require_course_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/panopto:view', $context);

// Trigger course_module_viewed event.
$params = array(
    'context' => $context,
    'objectid' => $panopto->id
);
$event = \mod_panopto\event\course_module_viewed::create($params);
$event->add_record_snapshot('course_modules', $cm);
$event->add_record_snapshot('course', $course);
$event->add_record_snapshot('panopto', $panopto);
$event->trigger();

// Mark module as viewed for course completion.
$completion = new completion_info($course);
$completion->set_module_viewed($cm);

$PAGE->set_url('/mod/panopto/view.php', array('id' => $cm->id));

// Instantiate Panopto client.
$panoptoclient = new repository_panopto_interface();
// Set current user.
$panoptoclient->set_authentication_info(
        get_config('panopto', 'instancename') . '\\' . $USER->username, '', get_config('panopto', 'applicationkey'));

// Perform the call to Panopto API to obtain viewer url.
$session = $panoptoclient->get_session_by_id($panopto->panoptosessionid, true);
if (!$session) {
    throw new invalid_parameter_exception(get_string('errorsessionnotfound', 'repository_panopto'));
}

// Grant access to the unique course module external group.
$panoptoaccess = $DB->get_record('panopto_user_access', array('userid'=> $USER->id, 'panoptoextgroupid' => $panopto->panoptoextgroupid));
if ($panoptoaccess) {
    // Access mapping exist, update access timestamp.
    $panoptoaccess->timeaccessed = time();
    $DB->update_record('panopto_user_access', $panoptoaccess);
} else {
    // User needs to be added to the group and access mapping record needs to be created.
    $panoptouser = $panoptoclient->sync_current_user();
    $groupexternalid = get_config('panopto', 'instancename') . '_cmid_' . $cm->id;
    $panoptoclient->add_member_to_external_group($groupexternalid, $panoptouser->getUserId());

    $panoptoaccess = new \stdClass();
    $panoptoaccess->userid = $USER->id;
    $panoptoaccess->panoptouserid = $panoptouser->getUserId();
    $panoptoaccess->panoptoextgroupid = $panopto->panoptoextgroupid;
    $panoptoaccess->timeaccessed = time();
    $DB->insert_record('panopto_user_access', $panoptoaccess);
}

// Perform the call to Panopto API to obtain authenticated url.
$authurl = $panoptoclient->get_authenticated_url($session->getViewerUrl());

// Redirect user to the session page on Panopto side.
redirect($authurl);