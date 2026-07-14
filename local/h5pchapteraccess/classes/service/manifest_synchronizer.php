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
use local_h5pchapteraccess\repository\book_repository;
use local_h5pchapteraccess\repository\chapter_repository;

/**
 * Synchronizes an extracted chapter manifest with persistent policy records.
 *
 * @package    local_h5pchapteraccess
 * @copyright  2026 Luiz Gustavo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class manifest_synchronizer {

    /** @var \moodle_database Moodle database connection. */
    private \moodle_database $db;

    /** @var book_repository Book persistence. */
    private book_repository $books;

    /** @var chapter_repository Chapter persistence. */
    private chapter_repository $chapters;

    /**
     * Constructor.
     *
     * @param book_repository|null $books Book repository
     * @param chapter_repository|null $chapters Chapter repository
     * @param \moodle_database|null $db Database connection
     */
    public function __construct(
        ?book_repository $books = null,
        ?chapter_repository $chapters = null,
        ?\moodle_database $db = null
    ) {
        global $DB;
        $this->db = $db ?? $DB;
        $this->books = $books ?? new book_repository($this->db);
        $this->chapters = $chapters ?? new chapter_repository($this->db);
    }

    /**
     * Synchronize the manifest atomically.
     *
     * Existing policy fields are preserved by chapter UUID. Missing chapters are retained as inactive.
     *
     * @param manifest $manifest Extracted manifest
     * @return array Counts keyed by created, updated, reactivated and deactivated
     */
    public function synchronize(manifest $manifest): array {
        $context = \context_module::instance($manifest->get_cmid());
        require_capability('local/h5pchapteraccess:manage', $context);

        return $this->synchronize_records($manifest);
    }

    /**
     * Synchronize after a policy request has already validated activity access.
     *
     * This entry point is intentionally separate from the management operation. It
     * must only be called after require_login and mod/h5pactivity:view checks.
     *
     * @param manifest $manifest Extracted server-side manifest
     * @return array Counts keyed by created, updated, reactivated and deactivated
     */
    public function synchronize_for_policy(manifest $manifest): array {
        $context = \context_module::instance($manifest->get_cmid());
        require_capability('mod/h5pactivity:view', $context);

        return $this->synchronize_records($manifest);
    }

    /**
     * Synchronize records atomically after the caller has performed authorization.
     *
     * @param manifest $manifest Extracted manifest
     * @return array Synchronization counts
     */
    private function synchronize_records(manifest $manifest): array {

        $transaction = $this->db->start_delegated_transaction();
        $summary = [
            'created' => 0,
            'updated' => 0,
            'reactivated' => 0,
            'deactivated' => 0,
            'unstableids' => [],
        ];

        $book = $this->books->save_manifest($manifest);
        $existing = $this->chapters->get_by_book_id((int) $book->id);
        $presentids = [];

        foreach ($manifest->get_chapters() as $chapter) {
            $chapterid = $chapter->get_id();
            $presentids[$chapterid] = true;
            if (!$chapter->is_stable()) {
                $summary['unstableids'][] = $chapterid;
            }

            if (!isset($existing[$chapterid])) {
                $this->chapters->create((int) $book->id, $chapter);
                $summary['created']++;
                continue;
            }

            $record = $existing[$chapterid];
            $wasinactive = !(bool) $record->active;
            $changed = $this->chapters->refresh($record, $chapter);
            if ($wasinactive) {
                $summary['reactivated']++;
            } else if ($changed) {
                $summary['updated']++;
            }
        }

        foreach ($existing as $chapterid => $record) {
            if (!isset($presentids[$chapterid]) && $this->chapters->deactivate($record)) {
                $summary['deactivated']++;
            }
        }

        $transaction->allow_commit();
        return $summary;
    }

    /**
     * Synchronize during a trusted Moodle restore after IDs were remapped.
     *
     * The restore caller supplies a server-extracted manifest. This method is
     * intentionally not used by browser entry points.
     *
     * @param manifest $manifest Restored activity manifest
     * @return array Synchronization counts
     */
    public function synchronize_after_restore(manifest $manifest): array {
        return $this->synchronize_records($manifest);
    }
}
