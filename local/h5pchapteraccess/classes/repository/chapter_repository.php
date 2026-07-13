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

use local_h5pchapteraccess\dto\chapter;

/**
 * Persistence operations for chapter policy records.
 *
 * @package    local_h5pchapteraccess
 * @copyright  2026 Luiz Gustavo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class chapter_repository {

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
     * Get all stored chapters keyed by chapter UUID.
     *
     * @param int $bookid Book record ID
     * @return \stdClass[]
     */
    public function get_by_book_id(int $bookid): array {
        $records = $this->db->get_records('local_h5pca_chapter', ['bookid' => $bookid]);
        $byuuid = [];
        foreach ($records as $record) {
            $byuuid[$record->chapteruuid] = $record;
        }
        return $byuuid;
    }

    /**
     * Create an open chapter record.
     *
     * @param int $bookid Book record ID
     * @param chapter $chapter Chapter manifest entry
     * @return \stdClass
     */
    public function create(int $bookid, chapter $chapter): \stdClass {
        $now = time();
        $record = (object) [
            'bookid' => $bookid,
            'chapteruuid' => $chapter->get_id(),
            'titlecache' => \core_text::substr($chapter->get_title(), 0, 255),
            'positioncache' => $chapter->get_position(),
            'stableid' => (int) $chapter->is_stable(),
            'accessmode' => 'open',
            'availabilityjson' => null,
            'lockedmessage' => null,
            'showrestriction' => 1,
            'active' => 1,
            'timecreated' => $now,
            'timemodified' => $now,
        ];
        $record->id = $this->db->insert_record('local_h5pca_chapter', $record);
        return $record;
    }

    /**
     * Refresh cached manifest fields and reactivate the record if needed.
     *
     * Access mode, availability JSON, messages and showrestriction are never changed here.
     *
     * @param \stdClass $record Existing record
     * @param chapter $chapter Current manifest entry
     * @return bool Whether any persisted field changed
     */
    public function refresh(\stdClass $record, chapter $chapter): bool {
        $title = \core_text::substr($chapter->get_title(), 0, 255);
        $changed = $record->titlecache !== $title
            || (int) $record->positioncache !== $chapter->get_position()
            || (bool) $record->stableid !== $chapter->is_stable()
            || !(bool) $record->active;

        if (!$changed) {
            return false;
        }

        $record->titlecache = $title;
        $record->positioncache = $chapter->get_position();
        $record->stableid = (int) $chapter->is_stable();
        $record->active = 1;
        $record->timemodified = time();
        $this->db->update_record('local_h5pca_chapter', $record);
        return true;
    }

    /**
     * Mark a chapter as absent without deleting its configuration.
     *
     * @param \stdClass $record Existing record
     * @return bool Whether the record was changed
     */
    public function deactivate(\stdClass $record): bool {
        if (!(bool) $record->active) {
            return false;
        }
        $record->active = 0;
        $record->timemodified = time();
        $this->db->update_record('local_h5pca_chapter', $record);
        return true;
    }
}
