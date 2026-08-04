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

namespace local_h5pchapteraccess\service;

use local_h5pchapteraccess\availability\chapter_info;
use local_h5pchapteraccess\dto\manifest;

/**
 * Loads, presents and persists one chapter's access configuration.
 *
 * @package    local_h5pchapteraccess
 * @copyright  2026 Luiz Gustavo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class chapter_configuration_service {

    /** Supported access modes. */
    public const ACCESS_MODES = ['open', 'locked', 'conditional'];

    /** @var \moodle_database Moodle database connection. */
    private \moodle_database $db;

    /**
     * Constructor.
     *
     * @param \moodle_database|null $db Database connection, primarily for tests
     */
    public function __construct(?\moodle_database $db = null) {
        global $DB;

        $this->db = $db ?? $DB;
    }

    /**
     * Load an active, stable chapter linked to both the activity and current manifest.
     *
     * @param int $cmid Course module ID
     * @param string $chapteruuid Stable chapter UUID
     * @param manifest $manifest Current trusted manifest
     * @return \stdClass Stored chapter record
     */
    public function require_editable_chapter(int $cmid, string $chapteruuid, manifest $manifest): \stdClass {
        $this->validate_chapter_uuid($chapteruuid);
        if ($manifest->get_cmid() !== $cmid) {
            throw new \coding_exception('The manifest does not belong to the requested course module.');
        }

        $manifestchapter = null;
        foreach ($manifest->get_chapters() as $chapter) {
            if (hash_equals($chapter->get_id(), $chapteruuid)) {
                $manifestchapter = $chapter;
                break;
            }
        }
        if ($manifestchapter === null) {
            throw new \moodle_exception('error:chapternotcurrent', 'local_h5pchapteraccess');
        }
        if (!$manifestchapter->is_stable()) {
            throw new \moodle_exception('error:unstablechapter', 'local_h5pchapteraccess');
        }

        $book = $this->db->get_record('local_h5pca_book', ['cmid' => $cmid]);
        if ($book === false) {
            throw new \moodle_exception('error:chapternotfound', 'local_h5pchapteraccess');
        }
        $record = $this->db->get_record('local_h5pca_chapter', [
            'bookid' => $book->id,
            'chapteruuid' => $chapteruuid,
        ]);
        if ($record === false) {
            throw new \moodle_exception('error:chapternotfound', 'local_h5pchapteraccess');
        }
        if (!(bool) $record->active) {
            throw new \moodle_exception('error:inactivechapter', 'local_h5pchapteraccess');
        }
        if (!(bool) $record->stableid) {
            throw new \moodle_exception('error:unstablechapter', 'local_h5pchapteraccess');
        }

        return $record;
    }

    /**
     * Build initial data for the chapter form.
     *
     * @param int $cmid Course module ID
     * @param \stdClass $chapter Chapter record
     * @return \stdClass
     */
    public function get_form_data(int $cmid, \stdClass $chapter): \stdClass {
        return (object) [
            'cmid' => $cmid,
            'chapter' => $chapter->chapteruuid,
            'accessmode' => $chapter->accessmode,
            'lockedmessage' => $chapter->lockedmessage ?? '',
            'showrestriction' => (int) $chapter->showrestriction,
            'availabilityconditionsjson' => $chapter->availabilityjson ?? '',
        ];
    }

    /**
     * Save one chapter after rechecking its activity and stable UUID link.
     *
     * Condition JSON is retained in every mode so temporarily selecting open
     * or locked does not destroy a previously configured tree. Runtime policy
     * evaluation uses it only for conditional mode.
     *
     * @param int $cmid Course module ID
     * @param string $chapteruuid Stable chapter UUID
     * @param \stdClass $data Validated form data
     */
    public function save(int $cmid, string $chapteruuid, \stdClass $data): void {
        global $CFG;

        $this->validate_chapter_uuid($chapteruuid);
        $context = \context_module::instance($cmid);
        require_capability('local/h5pchapteraccess:manage', $context);

        if ((int) ($data->cmid ?? 0) !== $cmid
                || !hash_equals($chapteruuid, (string) ($data->chapter ?? ''))) {
            throw new \invalid_parameter_exception('The submitted chapter does not match the requested chapter.');
        }

        $mode = (string) ($data->accessmode ?? '');
        if (!in_array($mode, self::ACCESS_MODES, true)) {
            throw new \invalid_parameter_exception('Invalid chapter access mode.');
        }
        if ($mode === 'conditional' && empty($CFG->enableavailability)) {
            throw new \invalid_parameter_exception('Conditional availability is disabled for this site.');
        }

        [$course, $cm] = get_course_and_cm_from_cmid($cmid);
        $transaction = $this->db->start_delegated_transaction();
        $book = $this->db->get_record('local_h5pca_book', ['cmid' => $cmid], '*', MUST_EXIST);
        $chapter = $this->db->get_record('local_h5pca_chapter', [
            'bookid' => $book->id,
            'chapteruuid' => $chapteruuid,
            'active' => 1,
            'stableid' => 1,
        ], '*', MUST_EXIST);

        if (property_exists($data, 'availabilityconditionsjson')) {
            $availability = trim((string) $data->availabilityconditionsjson);
            if ($mode === 'conditional') {
                $this->validate_availability_json($availability, $course, $cm, $chapter);
            }
            $chapter->availabilityjson = $availability === '' ? null : $availability;
        } else if ($mode === 'conditional') {
            throw new \invalid_parameter_exception('Conditional mode requires availability form data.');
        }

        $chapter->accessmode = $mode;
        $chapter->lockedmessage = $this->normalise_message($data->lockedmessage ?? '');
        $chapter->showrestriction = empty($data->showrestriction) ? 0 : 1;
        $chapter->timemodified = time();
        $this->db->update_record('local_h5pca_chapter', $chapter);
        $transaction->allow_commit();
    }

    /**
     * Add display-only mode and condition summary values to active chapters.
     *
     * @param \stdClass[] $chapters Chapter records
     * @param \stdClass $course Course record
     * @param \cm_info $cm Owning activity
     * @return \stdClass[] Defensive chapter copies
     */
    public function prepare_for_manage(array $chapters, \stdClass $course, \cm_info $cm): array {
        $prepared = [];
        foreach ($chapters as $chapter) {
            $item = clone $chapter;
            $item->accessmodelabel = $this->get_mode_label((string) $chapter->accessmode);
            $item->conditionsummaryhtml = $this->get_condition_summary($chapter, $course, $cm);
            $prepared[] = $item;
        }
        return $prepared;
    }

    /**
     * Get the configured Availability API tree as readable Moodle HTML.
     *
     * @param \stdClass $chapter Chapter record
     * @param \stdClass $course Course record
     * @param \cm_info $cm Owning activity
     * @return string Safe HTML generated by Moodle
     */
    public function get_condition_summary(\stdClass $chapter, \stdClass $course, \cm_info $cm): string {
        if (trim((string) ($chapter->availabilityjson ?? '')) === '') {
            return s(get_string('norestrictionsconfigured', 'local_h5pchapteraccess'));
        }

        $info = new chapter_info($course, $cm, $chapter);
        $summary = $info->get_full_information($cm->get_modinfo());
        if ($summary === false) {
            return \html_writer::span(
                s(get_string('availabilityinvalid', 'local_h5pchapteraccess')),
                'text-danger'
            );
        }

        $summary = \core_availability\info::format_info($summary, $course);
        if (trim(html_to_text($summary, 0, false)) === '') {
            return s(get_string('norestrictionsconfigured', 'local_h5pchapteraccess'));
        }
        return $summary;
    }

    /**
     * Get the translated label for a persisted mode.
     *
     * @param string $mode Access mode
     * @return string
     */
    public function get_mode_label(string $mode): string {
        $stringid = match ($mode) {
            'open' => 'modeopen',
            'locked' => 'modelocked',
            'conditional' => 'modeconditional',
            default => 'modeunavailable',
        };
        return get_string($stringid, 'local_h5pchapteraccess');
    }

    /**
     * Validate submitted JSON with the same frontend validation as core forms,
     * then decode the tree through the public Availability API.
     *
     * @param string $availability Availability JSON or empty string
     * @param \stdClass $course Course record
     * @param \cm_info $cm Owning activity
     * @param \stdClass $chapter Chapter record
     */
    private function validate_availability_json(
        string $availability,
        \stdClass $course,
        \cm_info $cm,
        \stdClass $chapter
    ): void {
        $errors = [];
        try {
            \core_availability\frontend::report_validation_errors([
                'availabilityconditionsjson' => $availability,
            ], $errors);
            if ($errors !== []) {
                throw new \invalid_parameter_exception(implode(' ', $errors));
            }
            if ($availability !== '') {
                $candidate = clone $chapter;
                $candidate->availabilityjson = $availability;
                (new chapter_info($course, $cm, $candidate))->get_availability_tree();
            }
        } catch (\coding_exception $exception) {
            throw new \invalid_parameter_exception(
                get_string('invalidavailabilityconditions', 'local_h5pchapteraccess')
            );
        }
    }

    /**
     * Validate a URL-facing chapter identifier before database use.
     *
     * @param string $chapteruuid Chapter UUID
     */
    private function validate_chapter_uuid(string $chapteruuid): void {
        if ($chapteruuid === ''
                || \core_text::strlen($chapteruuid) > 128
                || preg_match('/[\x00-\x1F\x7F]/u', $chapteruuid)) {
            throw new \invalid_parameter_exception('Invalid chapter identifier.');
        }
    }

    /**
     * Clean a plain-text message for persistence.
     *
     * @param mixed $message Submitted message
     * @return string|null
     */
    private function normalise_message($message): ?string {
        $message = trim(clean_param((string) $message, PARAM_TEXT));
        return $message === '' ? null : $message;
    }
}
