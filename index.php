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
 * List of Panopto resource modules in course.
 *
 * @package    mod_panopto
 * @copyright  2018 Lancaster University (http://www.lancaster.ac.uk/)
 * @author     Ruslan Kabalin (https://github.com/kabalin)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');

$id = required_param('id', PARAM_INT);
$course = $DB->get_record('course', array('id' => $id), '*', MUST_EXIST);
require_course_login($course, true);

// Prepare header.
$strpanoptos = get_string('modulenameplural', 'panopto');
$strname = get_string('name');
$strdescription = get_string('description');
$strsectionname = get_string('sectionname', 'format_' . $course->format);

$PAGE->set_pagelayout('incourse');
$PAGE->set_url('/mod/panopto/index.php', array('id' => $course->id));
$PAGE->set_title($course->shortname.': ' . $strpanoptos);
$PAGE->set_heading($course->fullname);
$PAGE->navbar->add($strpanoptos);
echo $OUTPUT->header();

// Prepare content.
if (!$panoptoresources = get_all_instances_in_course('panopto', $course)) {
    notice(get_string('thereareno', 'moodle', $strpanoptos), "$CFG->wwwroot/course/view.php?id=$course->id");
    exit;
}

$usesections = course_format_uses_sections($course->format);
if ($usesections) {
    $sections = get_fast_modinfo($course)->get_section_info_all();
}

$table = new html_table();
if ($usesections) {
    $table->head = array($strsectionname, $strname, $strdescription);
} else {
    $table->head = array($strname, $strdescription);
}

foreach ($panoptoresources as $panoptoresource) {
    // Link to resource.
    $linkcss = null;
    if (!$panoptoresource->visible) {
        $linkcss = array('class' => 'dimmed');
    }
    $icon = $OUTPUT->pix_icon('icon', '', 'mod_panopto', array('class' => 'smallicon pluginicon'));
    $link = $icon . html_writer::link(new moodle_url('/mod/panopto/view.php',
        array('id' => $panoptoresource->coursemodule)), $panoptoresource->name, $linkcss);

    // Properly format the intro.
    $panoptoresource->intro = format_module_intro('panopto', $panoptoresource, $panoptoresource->coursemodule);

    if ($usesections) {
        $table->data[] = array(get_section_name($course, $sections[$panoptoresource->section]), $link, $panoptoresource->intro);
    } else {
        $table->data[] = array($link, $panoptoresource->intro);
    }
}

echo html_writer::table($table);

// Log accessing this page.
$params = array(
    'context' => context_course::instance($course->id)
);
$event = \mod_panopto\event\course_module_instance_list_viewed::create($params);
$event->add_record_snapshot('course', $course);
$event->trigger();

echo $OUTPUT->footer();
