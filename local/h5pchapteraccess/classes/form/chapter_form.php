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

use local_h5pchapteraccess\service\chapter_configuration_service;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/**
 * Form that embeds Moodle's standard Availability API condition editor.
 *
 * @package    local_h5pchapteraccess
 * @copyright  2026 Luiz Gustavo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class chapter_form extends \moodleform {

    /**
     * Define chapter controls and the exact field structure expected by core JavaScript.
     */
    public function definition(): void {
        global $CFG, $OUTPUT;

        $mform = $this->_form;
        $mform->addElement('hidden', 'cmid', (int) $this->_customdata['cmid']);
        $mform->setType('cmid', PARAM_INT);
        $mform->addElement('hidden', 'chapter', (string) $this->_customdata['chapter']->chapteruuid);
        $mform->setType('chapter', PARAM_RAW_TRIMMED);

        $mform->addElement('header', 'chaptersettings', get_string('chaptersettings', 'local_h5pchapteraccess'));
        $mform->addElement(
            'select',
            'accessmode',
            get_string('accessmode', 'local_h5pchapteraccess'),
            [
                'open' => get_string('modeopen', 'local_h5pchapteraccess'),
                'locked' => get_string('modelocked', 'local_h5pchapteraccess'),
                'conditional' => get_string('modeconditional', 'local_h5pchapteraccess'),
            ]
        );
        $mform->setType('accessmode', PARAM_ALPHA);

        $mform->addElement(
            'textarea',
            'lockedmessage',
            get_string('specificmessage', 'local_h5pchapteraccess'),
            ['rows' => 4, 'cols' => 60]
        );
        $mform->setType('lockedmessage', PARAM_TEXT);
        $mform->addHelpButton('lockedmessage', 'specificmessage', 'local_h5pchapteraccess');

        $mform->addElement(
            'advcheckbox',
            'showrestriction',
            get_string('showrestriction', 'local_h5pchapteraccess')
        );
        $mform->addHelpButton('showrestriction', 'showrestriction', 'local_h5pchapteraccess');

        if (!empty($CFG->enableavailability)) {
            $mform->addElement(
                'header',
                'availabilityconditionsheader',
                get_string('restrictaccess', 'availability')
            );
            $mform->addElement(
                'static',
                'availabilitypreservationnotice',
                '',
                s(get_string('availabilitypreservationnotice', 'local_h5pchapteraccess'))
            );
            $mform->addElement(
                'textarea',
                'availabilityconditionsjson',
                get_string('accessrestrictions', 'availability'),
                ['class' => 'd-none']
            );
            $loadingcontainer = $OUTPUT->container(
                $OUTPUT->render_from_template('core/loading', []),
                'd-flex justify-content-center py-5 icon-size-5',
                'availabilityconditions-loading'
            );
            $mform->addElement('html', $loadingcontainer);
        } else {
            $warning = \html_writer::div(
                s(get_string('availabilitydisabled', 'local_h5pchapteraccess')),
                'alert alert-warning',
                ['role' => 'alert']
            );
            $mform->addElement('static', 'availabilitydisabled', '', $warning);
        }

        $this->add_action_buttons(true, get_string('savechanges'));
    }

    /**
     * Schedule the core editor after initial form values have been populated.
     */
    public function definition_after_data(): void {
        global $CFG;

        if (!empty($CFG->enableavailability)) {
            \core_availability\frontend::include_all_javascript(
                $this->_customdata['course'],
                $this->_customdata['cm']
            );
        }
    }

    /**
     * Apply the same Availability API validation used by Moodle module forms.
     *
     * @param array $data Submitted data
     * @param array $files Submitted files
     * @return array
     */
    public function validation($data, $files): array {
        global $CFG;

        $errors = parent::validation($data, $files);
        if (!in_array($data['accessmode'] ?? '', chapter_configuration_service::ACCESS_MODES, true)) {
            $errors['accessmode'] = get_string('invalidaccessmode', 'local_h5pchapteraccess');
        }
        if (($data['accessmode'] ?? '') === 'conditional' && empty($CFG->enableavailability)) {
            $errors['accessmode'] = get_string('availabilitydisabled', 'local_h5pchapteraccess');
        }

        if (!empty($CFG->enableavailability)) {
            try {
                \core_availability\frontend::report_validation_errors($data, $errors);
            } catch (\coding_exception $exception) {
                $errors['availabilityconditionsjson'] = get_string(
                    'invalidavailabilityconditions',
                    'local_h5pchapteraccess'
                );
            }
        }

        return $errors;
    }
}
