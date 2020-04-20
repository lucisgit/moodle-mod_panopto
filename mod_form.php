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
 * Panopto course module instance configuration.
 *
 * @package    mod_panopto
 * @copyright  2017 Lancaster University (http://www.lancaster.ac.uk/)
 * @author     Ruslan Kabalin (https://github.com/kabalin)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot . '/course/moodleform_mod.php');
require_once($CFG->dirroot . '/repository/panopto/form/panoptopicker.php');

class mod_panopto_mod_form extends moodleform_mod {

    public function definition() {
        global $CFG;
        $mform = $this->_form;

        // General.
        $mform->addElement('header', 'general', get_string('general', 'form'));
        $mform->addElement('text', 'name', get_string('name'), array('size' => '48'));
        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEANHTML);
        }
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');
        $this->standard_intro_elements();

        // Add Panoptopicker custom element.
        $mform->addElement('panoptopicker', 'panoptosessionid', get_string('video', 'panopto'));
        $mform->setType('panoptosessionid', PARAM_RAW_TRIMMED);

        // Add a text input field to enable a Panopto delivery id to be added manually.
        $mform->addElement('text', 'panoptodeliveryid', get_string('deliveryid', 'panopto'));
        $mform->setType('panoptodeliveryid', PARAM_TEXT);
        $mform->setAdvanced('panoptodeliveryid');
        $mform->addHelpButton('panoptodeliveryid', 'deliveryid', 'panopto');
        $mform->disabledIf('panoptodeliveryid', 'panoptosessionid', 'neq', '');

        // Standard coursemodule things.
        $this->standard_coursemodule_elements();
        $this->add_action_buttons();
    }

    /**
     * Any data processing needed before the form is displayed.
     *
     * @param array $defaultvalues
     */
    public function data_preprocessing(&$defaultvalues) {
        if (!empty($defaultvalues['panoptosessionid'])) {
            $defaultvalues['panoptodeliveryid'] = $defaultvalues['panoptosessionid'];
        }
    }

    /**
     * Validate the submitted form data.
     *
     * @param array $data Array of submitted data (element_name => value)
     * @param array $files Array of uploaded files (element_name => tmp_file_path)
     * @return array If there are errors (element_name => error_description)
     * @throws coding_exception
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        if (empty($data['panoptosessionid']) && empty($data['panoptodeliveryid'])) {
            $errors['panoptosessionid'] = get_string('novideo', 'panopto');
        }

        return $errors;
    }

    /**
     * Allows module to modify the data returned by form get_data().
     *
     * @param stdClass $data The form data to be modified.
     */
    public function data_postprocessing($data) {
        parent::data_postprocessing($data);

        if (empty($data->panoptosessionid) && !empty($data->panoptodeliveryid)) {
            $data->panoptosessionid = $data->panoptodeliveryid;
        }
    }

}
