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
 * External API for the Panopto module.
 *
 * @package     mod_panopto
 * @copyright   2020 Tony Butler <a.butler4@lancaster.ac.uk>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');
require_once($CFG->dirroot . '/mod/panopto/locallib.php');

/**
 * Library class for the Panopto module external API functions.
 *
 * @package     mod_panopto
 * @copyright   2020 Tony Butler <a.butler4@lancaster.ac.uk>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_panopto_external extends external_api {

    /**
     * Returns a description of the parameters for the get_auth method.
     *
     * @return external_function_parameters
     */
    public static function get_auth_parameters() {
        return new external_function_parameters(
            [
                'contextid' => new external_value(PARAM_INT, 'The context id of the Panopto instance'),
                'panoptoid' => new external_value(PARAM_INT, 'The instance id of the Panopto instance')
            ]
        );
    }

    /**
     * Requests and returns an auth url for the Panopto instance with the id provided.
     *
     * @param int $contextid The context id of the Panopto instance
     * @param int $panoptoid The instance id of the Panopto instance
     * @return string The auth url
     * @throws moodle_exception
     */
    public static function get_auth($contextid, $panoptoid) {
        global $DB;

        $parameters = [
            'contextid' => $contextid,
            'panoptoid' => $panoptoid
        ];
        self::validate_parameters(self::get_auth_parameters(), $parameters);
        $context = self::get_context_from_params(['contextid' => $contextid]);
        self::validate_context($context);

        if (!has_capability('mod/panopto:view', $context)) {
            throw new moodle_exception('nopermissions', 'mod_panopto');
        }

        $panopto = $DB->get_record('panopto', array('id' => $panoptoid), '*', MUST_EXIST);

        // Check if there is a valid auth url already.
        if ($authurl = get_valid_auth_url($panopto)) {
            return $authurl->panoptoauthurl;
        }
        // It is either expired or doesn't exist, let's schedule a new request if there isn't already one scheduled.
        if (!get_scheduled_task($panopto)) {
            schedule_task($context->instanceid, $panopto);
        }
    }

    /**
     * Returns a description of the result value for the get_auth method.
     *
     * @return external_description
     */
    public static function get_auth_returns() {
        return new external_value(PARAM_URL, 'The auth url to view the video');
    }

}
