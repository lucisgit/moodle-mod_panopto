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
 * Renders the HTML to display an instance of mod_panopto.
 *
 * @package     mod_panopto
 * @copyright   2020 Tony Butler <a.butler4@lancaster.ac.uk>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Panopto renderable class.
 *
 * @package     mod_panopto
 * @copyright   2020 Tony Butler <a.butler4@lancaster.ac.uk>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class panopto implements renderable {

    /** @var stdClass The course module info object */
    public $cm;

    /** @var context_module The module context object */
    public $context;

    /** @var int The Panopto instance id */
    public $id;

    /** @var string The Panopto video name */
    public $name;

    /** @var string The Panopto video intro */
    public $intro;

    /**
     * Panopto renderable constructor.
     *
     * @param cm_info $cm The course module info object
     * @param context_module $context The module context object
     * @param stdClass $panoptoinstance The Panopto instance
     */
    public function __construct(cm_info $cm, context_module $context, stdClass $panoptoinstance) {
        $this->cm      = $cm;
        $this->context = $context;
        $this->id      = $panoptoinstance->id;
        $this->name    = $panoptoinstance->name;
        $this->intro   = $panoptoinstance->intro;
    }

}

/**
 * Panopto renderer class.
 *
 * @package     mod_panopto
 * @copyright   2020 Tony Butler <a.butler4@lancaster.ac.uk>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_panopto_renderer extends plugin_renderer_base {

    /**
     * Renders a Panopto instance.
     *
     * @param panopto $panopto Panopto renderable
     * @return string The HTML to output
     */
    protected function render_panopto(panopto $panopto) {
        $out = $this->output->heading(format_string($panopto->name), 2);
        $out .= $this->output->box(format_text($panopto->intro));

        if (!has_capability('mod/panopto:view', $panopto->context)) {
            $out .= $this->output->notification(get_string('nopermissions', 'mod_panopto'), 'error');
            return $this->output->container($out);
        }

        $params = [
            'contextid' => $panopto->context->id,
            'panoptoid' => $panopto->id
        ];
        $this->page->requires->js_call_amd('mod_panopto/getauth', 'init', $params);

        return $this->output->container($out, '', 'panopto_info');
    }

}
