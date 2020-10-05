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
require_once($CFG->dirroot . '/mod/panopto/locallib.php');
require_once($CFG->libdir . '/completionlib.php');

$id = required_param('id', PARAM_INT);
list ($course, $cm) = get_course_and_cm_from_cmid($id, 'panopto');
$panoptoinstance = $DB->get_record('panopto', array('id' => $cm->instance), '*', MUST_EXIST);

require_course_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/panopto:view', $context);

// Trigger course_module_viewed event.
$params = array(
    'context' => $context,
    'objectid' => $panoptoinstance->id
);
$event = \mod_panopto\event\course_module_viewed::create($params);
$event->add_record_snapshot('course_modules', $cm);
$event->add_record_snapshot('course', $course);
$event->add_record_snapshot('panopto', $panoptoinstance);
$event->trigger();

// Mark module as viewed for course completion.
$completion = new completion_info($course);
$completion->set_module_viewed($cm);

// If there's an upgrade pending adhoc tasks will be suspended, so we have to resort to the old fashioned method.
if (!get_config('panopto', 'asynchronousmode') || moodle_needs_upgrading()) {
    // Set up remote permissions and get authenticated url.
    $authurl = setup_remote_permissions($cm->id, $panoptoinstance);

    // Redirect user to the session page on Panopto side.
    redirect($authurl);
}

$PAGE->set_url('/mod/panopto/view.php', array('id' => $cm->id));
$PAGE->set_title(format_string($panoptoinstance->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);

$output = $PAGE->get_renderer('mod_panopto');
$panopto = new panopto($cm, $context, $panoptoinstance);
echo $output->header();
echo $output->render($panopto);
echo $output->footer();
