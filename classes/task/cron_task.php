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
 * Panopto course module tasks.
 *
 * @package    mod_panopto
 * @copyright  2017 Lancaster University (http://www.lancaster.ac.uk/)
 * @author     Ruslan Kabalin (https://github.com/kabalin)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_panopto\task;

/**
 * Panopto course module cron task.
 *
 * @package    mod_panopto
 * @copyright  2017 Lancaster University (http://www.lancaster.ac.uk/)
 * @author     Ruslan Kabalin (https://github.com/kabalin)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class cron_task extends \core\task\scheduled_task {

    /**
     * Get a descriptive name for this task (shown to admins).
     *
     * @return string
     */
    public function get_name() {
        return get_string('crontask', 'mod_panopto');
    }

    /**
     * Run cron job to clear remote group membership for users who viewed the video already.
     */
    public function execute() {
        global $CFG, $DB;

        $delay = get_config('panopto', 'requiredaccesstime');
        if ($delay > 0) {
            require_once($CFG->dirroot . "/repository/panopto/locallib.php");
            // Instantiate Panopto client.
            $panoptoclient = new \repository_panopto_interface();

            $panoptoaccess = $DB->get_records_sql('SELECT * from {panopto_user_access} WHERE timeaccessed < :lastaccess',
                    array('lastaccess'=> time() - ($delay * 3600)));
            foreach ($panoptoaccess as $accessrecord) {

            }
        }
    }

}
