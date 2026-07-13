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

namespace local_h5pchapteraccess\repository;

use local_h5pchapteraccess\dto\manifest;

/**
 * Persistence operations for book policy records.
 *
 * @package    local_h5pchapteraccess
 * @copyright  2026 Luiz Gustavo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class book_repository {

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
     * Get a book record by course module ID.
     *
     * @param int $cmid Course module ID
     * @return \stdClass|null
     */
    public function get_by_cmid(int $cmid): ?\stdClass {
        $record = $this->db->get_record('local_h5pca_book', ['cmid' => $cmid]);
        return $record ?: null;
    }

    /**
     * Create or refresh the H5P identity fields without changing policy settings.
     *
     * @param manifest $manifest Extracted manifest
     * @return \stdClass Persisted record
     */
    public function save_manifest(manifest $manifest): \stdClass {
        $record = $this->get_by_cmid($manifest->get_cmid());
        $now = time();

        if ($record === null) {
            $record = (object) [
                'cmid' => $manifest->get_cmid(),
                'contentid' => $manifest->get_content_id(),
                'contenthash' => $manifest->get_content_hash(),
                'manifesthash' => $manifest->get_manifest_hash(),
                'enabled' => 1,
                'defaultmessage' => null,
                'timecreated' => $now,
                'timemodified' => $now,
            ];
            $record->id = $this->db->insert_record('local_h5pca_book', $record);
            return $record;
        }

        $record->contentid = $manifest->get_content_id();
        $record->contenthash = $manifest->get_content_hash();
        $record->manifesthash = $manifest->get_manifest_hash();
        $record->timemodified = $now;
        $this->db->update_record('local_h5pca_book', $record);

        return $record;
    }
}
