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
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle. If not, see <http://www.gnu.org/licenses/>.

namespace local_h5pchapteraccess\form;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/**
 * Activity-level chapter access settings.
 *
 * Chapter rules are edited separately on the same management page so the
 * teacher only sees one focused set of controls at a time.
 *
 * @package    local_h5pchapteraccess
 * @copyright  2026 Luiz Gustavo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class manage_form extends \moodleform {

    /** Define the concise activity settings form. */
    public function definition(): void {
        $mform = $this->_form;
        $cmid = (int) $this->_customdata['cmid'];

        $mform->addElement('hidden', 'cmid', $cmid);
        $mform->setType('cmid', PARAM_INT);

        $mform->addElement(
            'advcheckbox',
            'enabled',
            get_string('integrationenabledshort', 'local_h5pchapteraccess')
        );
        $mform->addHelpButton('enabled', 'integrationenabled', 'local_h5pchapteraccess');

        $mform->addElement(
            'textarea',
            'defaultmessage',
            get_string('defaultmessage', 'local_h5pchapteraccess'),
            [
                'rows' => 2,
                'cols' => 60,
                'placeholder' => get_string('defaultlockedmessage', 'local_h5pchapteraccess'),
            ]
        );
        $mform->setType('defaultmessage', PARAM_TEXT);
        $mform->addHelpButton('defaultmessage', 'defaultmessage', 'local_h5pchapteraccess');

        $this->add_action_buttons(false, get_string('savegeneralsettings', 'local_h5pchapteraccess'));
    }
}
