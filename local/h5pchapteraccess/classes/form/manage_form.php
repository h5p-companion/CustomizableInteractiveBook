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

use local_h5pchapteraccess\service\configuration_service;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/**
 * Manual chapter access configuration form.
 *
 * @package    local_h5pchapteraccess
 * @copyright  2026 Luiz Gustavo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class manage_form extends \moodleform {

    /**
     * Define the activity and chapter controls.
     */
    public function definition(): void {
        global $CFG;

        $mform = $this->_form;
        $cmid = (int) $this->_customdata['cmid'];
        $chapters = $this->_customdata['chapters'];

        $mform->addElement('hidden', 'cmid', $cmid);
        $mform->setType('cmid', PARAM_INT);

        $mform->addElement('header', 'activitysettings', get_string('activitysettings', 'local_h5pchapteraccess'));
        $mform->addElement(
            'advcheckbox',
            'enabled',
            get_string('integrationenabled', 'local_h5pchapteraccess')
        );
        $mform->addHelpButton('enabled', 'integrationenabled', 'local_h5pchapteraccess');

        $mform->addElement(
            'textarea',
            'defaultmessage',
            get_string('defaultmessage', 'local_h5pchapteraccess'),
            ['rows' => 4, 'cols' => 60]
        );
        $mform->setType('defaultmessage', PARAM_TEXT);
        $mform->addHelpButton('defaultmessage', 'defaultmessage', 'local_h5pchapteraccess');

        $mform->addElement('header', 'activechapters', get_string('activechapters', 'local_h5pchapteraccess'));
        if ($chapters === []) {
            $mform->addElement(
                'static',
                'noactivechapters',
                '',
                s(get_string('noactivechapters', 'local_h5pchapteraccess'))
            );
        }

        foreach ($chapters as $chapter) {
            $recordid = (int) $chapter->id;
            $position = (int) $chapter->positioncache + 1;
            $mform->addElement(
                'header',
                'chapter_' . $recordid,
                get_string('chapterpositionheading', 'local_h5pchapteraccess', $position)
            );
            $mform->addElement(
                'static',
                'chaptertitle_' . $recordid,
                get_string('chaptertitle', 'local_h5pchapteraccess'),
                s((string) $chapter->titlecache)
            );
            $mform->addElement(
                'static',
                'chapteruuid_' . $recordid,
                get_string('chapteruuid', 'local_h5pchapteraccess'),
                s((string) $chapter->chapteruuid)
            );
            $mform->addElement(
                'static',
                'stableid_' . $recordid,
                get_string('stableid', 'local_h5pchapteraccess'),
                s(get_string(
                    (bool) $chapter->stableid ? 'stableidyes' : 'stableidno',
                    'local_h5pchapteraccess'
                ))
            );
            $mform->addElement(
                'static',
                'conditionsummary_' . $recordid,
                get_string('conditionssummary', 'local_h5pchapteraccess'),
                $chapter->conditionsummaryhtml
            );

            if (!(bool) $chapter->stableid) {
                $warning = \html_writer::div(
                    s(get_string('unstableidwarning', 'local_h5pchapteraccess')),
                    'alert alert-warning',
                    ['role' => 'alert']
                );
                $mform->addElement('static', 'unstablewarning_' . $recordid, '', $warning);
                $mform->addElement(
                    'static',
                    'unstablemode_' . $recordid,
                    get_string('accessmode', 'local_h5pchapteraccess'),
                    s($this->mode_label((string) $chapter->accessmode))
                );
                $disabledbutton = \html_writer::span(
                    get_string('editrestrictions', 'local_h5pchapteraccess'),
                    'btn btn-secondary disabled',
                    ['aria-disabled' => 'true']
                );
                $mform->addElement('static', 'editrestrictions_' . $recordid, '', $disabledbutton);
                continue;
            }

            if ($chapter->accessmode === 'conditional' && $chapter->editurl instanceof \moodle_url) {
                $editbutton = \html_writer::link(
                    $chapter->editurl,
                    get_string('editrestrictions', 'local_h5pchapteraccess'),
                    ['class' => 'btn btn-secondary']
                );
                $mform->addElement('static', 'editrestrictions_' . $recordid, '', $editbutton);
            }

            if (!in_array($chapter->accessmode, configuration_service::EDITABLE_MODES, true)) {
                $warning = \html_writer::div(
                    s(get_string('unsupportedmodewarning', 'local_h5pchapteraccess')),
                    'alert alert-warning',
                    ['role' => 'alert']
                );
                $mform->addElement('static', 'modewarning_' . $recordid, '', $warning);
                $mform->addElement(
                    'static',
                    'unsupportedmode_' . $recordid,
                    get_string('accessmode', 'local_h5pchapteraccess'),
                    s(get_string('modeunavailable', 'local_h5pchapteraccess'))
                );
                continue;
            }

            $modefield = 'accessmode_' . $recordid;
            $messagefield = 'lockedmessage_' . $recordid;
            $mform->addElement(
                'select',
                $modefield,
                get_string('accessmode', 'local_h5pchapteraccess'),
                [
                    'open' => get_string('modeopen', 'local_h5pchapteraccess'),
                    'locked' => get_string('modelocked', 'local_h5pchapteraccess'),
                    'conditional' => get_string('modeconditional', 'local_h5pchapteraccess'),
                ]
            );
            $mform->setType($modefield, PARAM_ALPHA);

            $mform->addElement(
                'textarea',
                $messagefield,
                get_string('specificmessage', 'local_h5pchapteraccess'),
                ['rows' => 3, 'cols' => 60]
            );
            $mform->setType($messagefield, PARAM_TEXT);
            $mform->addHelpButton($messagefield, 'specificmessage', 'local_h5pchapteraccess');
            $mform->disabledIf($messagefield, $modefield, 'eq', 'open');

            if ($chapter->accessmode !== 'conditional') {
                $mform->addElement(
                    'static',
                    'conditionalhint_' . $recordid,
                    '',
                    s(get_string(
                        empty($CFG->enableavailability) ? 'availabilitydisabled' : 'conditionaleditafter_save',
                        'local_h5pchapteraccess'
                    ))
                );
            }
        }

        $this->add_action_buttons(true, get_string('savechanges'));
    }

    /**
     * Validate that only the modes exposed by this form can be submitted.
     *
     * @param array $data Submitted data
     * @param array $files Submitted files
     * @return array
     */
    public function validation($data, $files): array {
        global $CFG;

        $errors = parent::validation($data, $files);
        foreach ($this->_customdata['chapters'] as $chapter) {
            if (!(bool) $chapter->stableid
                    || !in_array($chapter->accessmode, configuration_service::EDITABLE_MODES, true)) {
                continue;
            }
            $field = 'accessmode_' . $chapter->id;
            if (isset($data[$field]) && !in_array($data[$field], configuration_service::EDITABLE_MODES, true)) {
                $errors[$field] = get_string('invalidaccessmode', 'local_h5pchapteraccess');
            }
            if (($data[$field] ?? '') === 'conditional' && empty($CFG->enableavailability)) {
                $errors[$field] = get_string('availabilitydisabled', 'local_h5pchapteraccess');
            }
        }
        return $errors;
    }

    /**
     * Get a user-facing label for an already persisted mode.
     *
     * @param string $mode Persisted mode
     * @return string
     */
    private function mode_label(string $mode): string {
        if ($mode === 'open') {
            return get_string('modeopen', 'local_h5pchapteraccess');
        }
        if ($mode === 'locked') {
            return get_string('modelocked', 'local_h5pchapteraccess');
        }
        if ($mode === 'conditional') {
            return get_string('modeconditional', 'local_h5pchapteraccess');
        }
        return get_string('modeunavailable', 'local_h5pchapteraccess');
    }
}
