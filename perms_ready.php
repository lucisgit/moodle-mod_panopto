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
require('../../config.php');
require_once($CFG->dirroot.'/mod/panopto/lib.php');

require_login();

// This is a "fast" query to see if there is an auth url and get the value, it will render faster than the view page so
// might be better from a load point of view. You could do the same in a webservice which would be faster still, consider it a POC.

// Get params.
$panoptosessionid = required_param('sessionid', PARAM_TEXT);
$cmid = required_param('cmid', PARAM_TEXT);

// Get data.
list ($course, $cm) = get_course_and_cm_from_cmid($cmid, 'panopto');
$panopto = $DB->get_record('panopto', array('id' => $cm->instance), '*', MUST_EXIST);

// Check if we are authed already.
if ($panoptoauthurl = panopto_get_auth_url($panopto)) {
    echo $panoptoauthurl->panoptoauthurl;
} else {
    // Check if we are scheduled already.
    if (!panopto_get_scheduled($panopto)) {
        // Schedule the perms tasks.
        panopto_schedule($cmid, $panopto);
    }
    echo "false";
}
