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

        $mform->addElement('header', 'configurationguide', get_string(
            'configurationguide',
            'local_h5pchapteraccess'
        ));
        $mform->addElement(
            'static',
            'configurationguideintro',
            '',
            s(get_string('configurationguideintro', 'local_h5pchapteraccess'))
        );
        $mform->addElement(
            'static',
            'accessmodeguide',
            get_string('accessmode', 'local_h5pchapteraccess'),
            $this->mode_guide()
        );

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
            [
                'rows' => 4,
                'cols' => 60,
                'placeholder' => get_string('defaultlockedmessage', 'local_h5pchapteraccess'),
            ]
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
                    . ' — ' . s((string) $chapter->titlecache)
            );
            $mform->addElement(
                'static',
                'currentmode_' . $recordid,
                get_string('currentmode', 'local_h5pchapteraccess'),
                \html_writer::span(
                    s((string) $chapter->accessmodelabel),
                    'badge ' . $this->mode_badge_class((string) $chapter->accessmode)
                )
            );
            $mform->addElement(
                'static',
                'technicaldetails_' . $recordid,
                '',
                $this->technical_details($chapter)
            );

            if ($chapter->accessmode === 'conditional'
                    || trim((string) ($chapter->availabilityjson ?? '')) !== '') {
                $mform->addElement(
                    'static',
                    'conditionsummary_' . $recordid,
                    get_string('conditionssummary', 'local_h5pchapteraccess'),
                    $chapter->conditionsummaryhtml
                );
            }

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
            $mform->addHelpButton($modefield, 'accessmode', 'local_h5pchapteraccess');

            $mform->addElement(
                'textarea',
                $messagefield,
                get_string('specificmessage', 'local_h5pchapteraccess'),
                ['rows' => 3, 'cols' => 60]
            );
            $mform->setType($messagefield, PARAM_TEXT);
            $mform->addHelpButton($messagefield, 'specificmessage', 'local_h5pchapteraccess');
            $mform->disabledIf($messagefield, $modefield, 'eq', 'open');

            if (empty($CFG->enableavailability)) {
                $mform->addElement(
                    'static',
                    'conditionalhint_' . $recordid,
                    '',
                    \html_writer::div(
                        s(get_string('availabilitydisabled', 'local_h5pchapteraccess')),
                        'alert alert-warning',
                        ['role' => 'alert']
                    )
                );
            } else if ($chapter->editurl instanceof \moodle_url) {
                $editbutton = \html_writer::link(
                    $chapter->editurl,
                    get_string('configureconditions', 'local_h5pchapteraccess'),
                    ['class' => 'btn btn-secondary']
                );
                $hint = \html_writer::div(
                    s(get_string('conditionconfigurationhint', 'local_h5pchapteraccess')),
                    'small text-muted mt-2'
                );
                $mform->addElement(
                    'static',
                    'editrestrictions_' . $recordid,
                    get_string('conditionalsettings', 'local_h5pchapteraccess'),
                    $editbutton . $hint
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

    /**
     * Build the short explanation shown before chapter controls.
     *
     * @return string Safe HTML
     */
    private function mode_guide(): string {
        $items = [
            \html_writer::tag('strong', s(get_string('modeopen', 'local_h5pchapteraccess')))
                . ': ' . s(get_string('modeopendescription', 'local_h5pchapteraccess')),
            \html_writer::tag('strong', s(get_string('modelocked', 'local_h5pchapteraccess')))
                . ': ' . s(get_string('modelockeddescription', 'local_h5pchapteraccess')),
            \html_writer::tag('strong', s(get_string('modeconditional', 'local_h5pchapteraccess')))
                . ': ' . s(get_string('modeconditionaldescription', 'local_h5pchapteraccess')),
        ];

        return \html_writer::alist($items);
    }

    /**
     * Select a Bootstrap badge class for a persisted mode.
     *
     * @param string $mode Access mode
     * @return string
     */
    private function mode_badge_class(string $mode): string {
        return match ($mode) {
            'open' => 'bg-success text-white',
            'locked' => 'bg-danger text-white',
            'conditional' => 'bg-warning text-dark',
            default => 'bg-secondary text-white',
        };
    }

    /**
     * Hide technical chapter identity behind an expandable disclosure.
     *
     * @param \stdClass $chapter Prepared chapter record
     * @return string Safe HTML
     */
    private function technical_details(\stdClass $chapter): string {
        $id = \html_writer::tag('code', s((string) $chapter->chapteruuid));
        $stable = s(get_string(
            (bool) $chapter->stableid ? 'stableidyes' : 'stableidno',
            'local_h5pchapteraccess'
        ));
        $body = \html_writer::div(
            \html_writer::tag('strong', s(get_string('chapteruuid', 'local_h5pchapteraccess')))
                . ': ' . $id . \html_writer::empty_tag('br')
                . \html_writer::tag('strong', s(get_string('stableid', 'local_h5pchapteraccess')))
                . ': ' . $stable,
            'mt-2'
        );

        return \html_writer::tag(
            'details',
            \html_writer::tag('summary', s(get_string('technicaldetails', 'local_h5pchapteraccess')))
                . $body
        );
    }
}
