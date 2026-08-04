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

use local_h5pchapteraccess\dto\manifest;
use local_h5pchapteraccess\exception\invalid_activity_exception;
use local_h5pchapteraccess\repository\book_repository;
use local_h5pchapteraccess\repository\chapter_repository;
use mod_h5pactivity\local\manager;

/**
 * Coordinates access checks, synchronization and manual configuration persistence.
 *
 * @package    local_h5pchapteraccess
 * @copyright  2026 Luiz Gustavo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class configuration_service {

    /** Modes editable by the manual interface. */
    public const EDITABLE_MODES = ['open', 'locked', 'conditional'];

    /** @var \moodle_database Moodle database connection. */
    private \moodle_database $db;

    /** @var manifest_extractor Manifest extractor. */
    private manifest_extractor $extractor;

    /** @var manifest_synchronizer Manifest synchronizer. */
    private manifest_synchronizer $synchronizer;

    /** @var book_repository Book repository. */
    private book_repository $books;

    /** @var chapter_repository Chapter repository. */
    private chapter_repository $chapters;

    /**
     * Constructor.
     *
     * @param \moodle_database|null $db Database connection
     * @param manifest_extractor|null $extractor Manifest extractor
     * @param manifest_synchronizer|null $synchronizer Manifest synchronizer
     */
    public function __construct(
        ?\moodle_database $db = null,
        ?manifest_extractor $extractor = null,
        ?manifest_synchronizer $synchronizer = null
    ) {
        global $DB;

        $this->db = $db ?? $DB;
        $this->extractor = $extractor ?? new manifest_extractor();
        $this->books = new book_repository($this->db);
        $this->chapters = new chapter_repository($this->db);
        $this->synchronizer = $synchronizer ?? new manifest_synchronizer(
            $this->books,
            $this->chapters,
            $this->db
        );
    }

    /**
     * Load and authorize one manageable h5pactivity.
     *
     * @param int $cmid Course module ID
     * @return \stdClass Object containing course, cm, context and activity
     * @throws invalid_activity_exception
     */
    public function require_manageable_activity(int $cmid): \stdClass {
        if ($cmid <= 0) {
            throw new invalid_activity_exception(invalid_activity_exception::CM_NOT_FOUND, $cmid);
        }

        try {
            [$course, $cm] = get_course_and_cm_from_cmid($cmid);
        } catch (\moodle_exception $exception) {
            throw new invalid_activity_exception(
                invalid_activity_exception::CM_NOT_FOUND,
                $cmid,
                $exception->getMessage()
            );
        }

        if ($cm->modname !== 'h5pactivity') {
            throw new invalid_activity_exception(invalid_activity_exception::WRONG_MODULE, $cm->modname);
        }

        $context = \context_module::instance($cm->id);
        try {
            require_login($course, true, $cm);
            require_capability('local/h5pchapteraccess:manage', $context);
        } catch (\moodle_exception $exception) {
            throw new invalid_activity_exception(
                invalid_activity_exception::ACCESS_DENIED,
                $cmid,
                $exception->getMessage()
            );
        }

        try {
            $activity = manager::create_from_coursemodule($cm)->get_instance();
        } catch (\dml_exception $exception) {
            throw new invalid_activity_exception(
                invalid_activity_exception::INSTANCE_NOT_FOUND,
                $cmid,
                $exception->getMessage()
            );
        }

        return (object) [
            'course' => $course,
            'cm' => $cm,
            'context' => $context,
            'activity' => $activity,
        ];
    }

    /**
     * Extract the current manifest after normal activity access checks.
     *
     * @param int $cmid Course module ID
     * @return manifest
     */
    public function extract_manifest(int $cmid): manifest {
        return $this->extractor->extract($cmid);
    }

    /**
     * Determine whether persistence differs from the extracted manifest.
     *
     * @param manifest $manifest Current manifest
     * @param \stdClass|null $book Already loaded book record
     * @param array|null $stored Already loaded chapters keyed by UUID
     * @return bool
     */
    public function needs_synchronization(
        manifest $manifest,
        ?\stdClass $book = null,
        ?array $stored = null
    ): bool {
        $book = $book ?? $this->books->get_by_cmid($manifest->get_cmid());
        if ($book === null
                || (int) $book->contentid !== $manifest->get_content_id()
                || $book->contenthash !== $manifest->get_content_hash()
                || $book->manifesthash !== $manifest->get_manifest_hash()) {
            return true;
        }

        $stored = $stored ?? $this->chapters->get_by_book_id((int) $book->id);
        $manifestids = [];
        foreach ($manifest->get_chapters() as $chapter) {
            $chapterid = $chapter->get_id();
            $manifestids[$chapterid] = true;
            if (!isset($stored[$chapterid])) {
                return true;
            }

            $record = $stored[$chapterid];
            if (!(bool) $record->active
                    || $record->titlecache !== \core_text::substr($chapter->get_title(), 0, 255)
                    || (int) $record->positioncache !== $chapter->get_position()
                    || (bool) $record->stableid !== $chapter->is_stable()) {
                return true;
            }
        }

        foreach ($stored as $chapterid => $record) {
            if ((bool) $record->active && !isset($manifestids[$chapterid])) {
                return true;
            }
        }

        return false;
    }

    /**
     * Synchronize a previously extracted manifest.
     *
     * @param manifest $manifest Current manifest
     * @return array Synchronization summary
     */
    public function synchronize_manifest(manifest $manifest): array {
        return $this->synchronizer->synchronize($manifest);
    }

    /**
     * Load the persisted configuration ordered for display.
     *
     * @param int $cmid Course module ID
     * @return \stdClass Object containing book, activechapters and inactivechapters
     */
    public function get_configuration(int $cmid): \stdClass {
        $book = $this->books->get_by_cmid($cmid);
        if ($book === null) {
            throw new \moodle_exception('error:configurationmissing', 'local_h5pchapteraccess');
        }

        return (object) [
            'book' => $book,
            'activechapters' => array_values($this->db->get_records(
                'local_h5pca_chapter',
                ['bookid' => $book->id, 'active' => 1],
                'positioncache ASC, id ASC'
            )),
            'inactivechapters' => array_values($this->db->get_records(
                'local_h5pca_chapter',
                ['bookid' => $book->id, 'active' => 0],
                'positioncache ASC, id ASC'
            )),
        ];
    }

    /**
     * Build safe initial data for the activity-level settings form.
     *
     * @param \stdClass $configuration Configuration returned by get_configuration()
     * @return \stdClass
     */
    public function get_form_data(\stdClass $configuration): \stdClass {
        return (object) [
            'cmid' => (int) $configuration->book->cmid,
            'enabled' => (int) $configuration->book->enabled,
            'defaultmessage' => $configuration->book->defaultmessage ?? '',
        ];
    }

    /**
     * Save manual activity and chapter settings atomically.
     *
     * Only active chapters with stable IDs and already-editable modes are considered.
     *
     * @param int $cmid Course module ID
     * @param \stdClass $data Validated form data
     */
    public function save(int $cmid, \stdClass $data): void {
        global $CFG;

        $context = \context_module::instance($cmid);
        require_capability('local/h5pchapteraccess:manage', $context);

        $transaction = $this->db->start_delegated_transaction();
        $book = $this->db->get_record('local_h5pca_book', ['cmid' => $cmid], '*', MUST_EXIST);
        $now = time();

        $book->enabled = empty($data->enabled) ? 0 : 1;
        $book->defaultmessage = $this->normalise_message($data->defaultmessage ?? '');
        $book->timemodified = $now;
        $this->db->update_record('local_h5pca_book', $book);

        $chapters = $this->db->get_records(
            'local_h5pca_chapter',
            ['bookid' => $book->id, 'active' => 1, 'stableid' => 1]
        );
        foreach ($chapters as $chapter) {
            if (!in_array($chapter->accessmode, self::EDITABLE_MODES, true)) {
                // Preserve modes reserved for future interfaces without exposing or overwriting them.
                continue;
            }

            $modefield = 'accessmode_' . $chapter->id;
            $messagefield = 'lockedmessage_' . $chapter->id;
            $mode = property_exists($data, $modefield) ? $data->{$modefield} : $chapter->accessmode;
            if (!in_array($mode, self::EDITABLE_MODES, true)) {
                throw new \invalid_parameter_exception('Invalid chapter access mode.');
            }
            if ($mode === 'conditional' && empty($CFG->enableavailability)) {
                throw new \invalid_parameter_exception('Conditional availability is disabled for this site.');
            }

            $chapter->accessmode = $mode;
            if (property_exists($data, $messagefield)) {
                $chapter->lockedmessage = $this->normalise_message($data->{$messagefield});
            }
            $chapter->timemodified = $now;
            $this->db->update_record('local_h5pca_chapter', $chapter);
        }

        $transaction->allow_commit();
    }

    /**
     * Save only the concise activity-level settings form.
     *
     * Chapter rules are edited by {@see chapter_configuration_service}; keeping
     * this operation separate prevents an unrelated general save from touching
     * chapter timestamps or revalidating stored conditional rules.
     *
     * @param int $cmid Course module ID
     * @param \stdClass $data Validated form data
     */
    public function save_activity_settings(int $cmid, \stdClass $data): void {
        $context = \context_module::instance($cmid);
        require_capability('local/h5pchapteraccess:manage', $context);

        $transaction = $this->db->start_delegated_transaction();
        $book = $this->db->get_record('local_h5pca_book', ['cmid' => $cmid], '*', MUST_EXIST);
        $book->enabled = empty($data->enabled) ? 0 : 1;
        $book->defaultmessage = $this->normalise_message($data->defaultmessage ?? '');
        $book->timemodified = time();
        $this->db->update_record('local_h5pca_book', $book);
        $transaction->allow_commit();
    }

    /**
     * Resolve the plain-text lock message with chapter precedence.
     *
     * @param \stdClass $book Book record
     * @param \stdClass $chapter Chapter record
     * @return string
     */
    public function get_effective_locked_message(\stdClass $book, \stdClass $chapter): string {
        $specific = trim((string) ($chapter->lockedmessage ?? ''));
        if ($specific !== '') {
            return $specific;
        }
        return trim((string) ($book->defaultmessage ?? ''));
    }

    /**
     * Clean a plain-text message for persistence.
     *
     * @param mixed $message Submitted value
     * @return string|null
     */
    private function normalise_message($message): ?string {
        $message = trim(clean_param((string) $message, PARAM_TEXT));
        return $message === '' ? null : $message;
    }
}
