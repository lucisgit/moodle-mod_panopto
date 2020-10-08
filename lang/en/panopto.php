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
 * Strings for component 'panopto', language 'en'
 *
 * @package    mod_panopto
 * @copyright  2017 Lancaster University (http://www.lancaster.ac.uk/)
 * @author     Ruslan Kabalin (https://github.com/kabalin)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['asynchronousmode'] = 'Asynchronous mode';
$string['asynchronousmode_desc'] = 'In asynchronous mode, the access request that is submitted to Panopto when a user clicks a video link, is queued and executed as a background task. Under normal circumstances this task would just be executed immediately, but in the event of a whole class of students attempting to access a video simultaneously, handling the requests in this way should prevent Moodle becoming overloaded.<br><br>In order to make this mode possible, you would need to set up an additional cron task to monitor the adhoc tasks queue, e.g. something like:<pre>* * * * * www-data /usr/bin/php /var/www/moodle/admin/tool/task/cli/adhoc_task.php -e --keep-alive=600 > /dev/null</pre>Note that this example runs the task every minute and keeps it running for 10 minutes, enabling up to 10 requests to be handled in parallel.<br><br>If in doubt, leave this option switched off.';
$string['chooseavideo'] = 'Choose Panopto video';
$string['crontask'] = 'Panopto course module: remote group membership clearing';
$string['deliveryid'] = 'Delivery ID';
$string['deliveryid_help'] = 'If your video is not yet available via the Panopto navigator above, you can simply paste its Delivery ID here.<br><br>The Delivery ID can be found for each recording in the Panopto portal via Settings.';
$string['modulename'] = 'Panopto video';
$string['modulename_help'] = 'The Panopto resource enables a teacher to create a reference to any Panopto video. Just pick the video using repository navigation interface and any student who have access to this resource in Moodle will be able to watch the video on Panopto server.';
$string['modulename_link'] = 'mod/panopto/view';
$string['modulenameplural'] = 'Panopto videos';
$string['nopermissions'] = 'Sorry, you don\'t have the necessary permissions to view this Panopto video.';
$string['novideo'] = 'You must either browse for and select a video, or enter a Panopto Delivery ID.';
$string['off'] = 'Off';
$string['on'] = 'On';
$string['panopto:addinstance'] = 'Add new Panopto resource';
$string['panopto:view'] = 'View Panopto video';
$string['pluginadministration'] = 'Panopto module administration';
$string['pluginname'] = 'Panopto';
$string['preparing'] = 'Preparing video ...';
$string['requiredaccesstime'] = 'Access timeout';
$string['requiredaccesstime_desc'] = 'On the attempt to view Panopto video, any user who can access module will be added to the dedicated group on Panopto site and redirected to view video on Panopto. This setting defines how long (in hours) user will be able to access video via Panopto site. Access will be seised in a delay defind above, to view the video user will need to access it from Moodle again. If set to unlimited, user will be able to view video via Panopto site till the course module exists, despite whether she is still enrolled and can access the module or not.';
$string['unlimited'] = 'Unlimited';
$string['usereposettings'] = 'This plugin requires Panopto repository plugin to be installed and <a href="{$a}">configured</a>.';
$string['video'] = 'Video';
