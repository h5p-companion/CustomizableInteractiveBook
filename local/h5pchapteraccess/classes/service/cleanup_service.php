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

/**
 * Removes activity-scoped plugin data after permanent Moodle deletion.
 *
 * @package    local_h5pchapteraccess
 * @copyright  2026 Luiz Gustavo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class cleanup_service {

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
     * Delete one activity configuration atomically.
     *
     * @param int $cmid Deleted course module ID
     */
    public function delete_by_cmid(int $cmid): void {
        $transaction = $this->db->start_delegated_transaction();
        $book = $this->db->get_record('local_h5pca_book', ['cmid' => $cmid], 'id');
        if ($book) {
            $this->db->delete_records('local_h5pca_chapter', ['bookid' => $book->id]);
            $this->db->delete_records('local_h5pca_book', ['id' => $book->id]);
        }
        $transaction->allow_commit();
    }

    /**
     * Remove any plugin records whose course module no longer exists.
     *
     * Course deletion emits module-deleted events first. This defensive pass
     * covers interrupted, legacy or externally altered deletion workflows.
     *
     * @return int Number of orphan book records removed
     */
    public function delete_orphans(): int {
        $sql = 'SELECT b.id, b.cmid
                  FROM {local_h5pca_book} b
             LEFT JOIN {course_modules} cm ON cm.id = b.cmid
                 WHERE cm.id IS NULL';
        $orphans = $this->db->get_records_sql($sql);
        if (!$orphans) {
            return 0;
        }

        $transaction = $this->db->start_delegated_transaction();
        foreach ($orphans as $book) {
            $this->db->delete_records('local_h5pca_chapter', ['bookid' => $book->id]);
            $this->db->delete_records('local_h5pca_book', ['id' => $book->id]);
        }
        $transaction->allow_commit();
        return count($orphans);
    }
}
