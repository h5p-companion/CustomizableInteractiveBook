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

/**
 * Activity-level restore support for local_h5pchapteraccess.
 *
 * @package    local_h5pchapteraccess
 * @copyright  2026 Luiz Gustavo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

use local_h5pchapteraccess\availability\chapter_info;
use local_h5pchapteraccess\service\manifest_cache;
use local_h5pchapteraccess\service\manifest_extractor;
use local_h5pchapteraccess\service\manifest_synchronizer;

/**
 * Restores chapter access configuration against the new course module ID.
 */
class restore_local_h5pchapteraccess_plugin extends restore_local_plugin {

    /** @var int|null New plugin book record ID. */
    private ?int $restoredbookid = null;

    /**
     * Define paths connected to one restored course module.
     *
     * @return restore_path_element[]
     */
    protected function define_module_plugin_structure(): array {
        return [
            new restore_path_element(
                $this->get_namefor('book'),
                $this->get_pathfor('/book')
            ),
            new restore_path_element(
                $this->get_namefor('chapter'),
                $this->get_pathfor('/book/chapter')
            ),
        ];
    }

    /**
     * Restore the activity-level configuration with the new cmid.
     *
     * H5P identity hashes are intentionally not copied: lazy synchronization
     * must establish them from the restored package.
     *
     * @param array $data Backup data
     */
    public function process_local_h5pchapteraccess_book(array $data): void {
        global $DB;

        $record = (object) $data;
        $oldid = (int) $record->id;
        $cmid = (int) $this->get_task()->get_moduleid();
        $now = time();

        $book = $DB->get_record('local_h5pca_book', ['cmid' => $cmid]);
        if (!$book) {
            $book = (object) [
                'cmid' => $cmid,
                'contentid' => null,
                'contenthash' => null,
                'manifesthash' => null,
                'enabled' => empty($record->enabled) ? 0 : 1,
                'defaultmessage' => $this->clean_message($record->defaultmessage ?? null),
                'timecreated' => $now,
                'timemodified' => $now,
            ];
            $book->id = $DB->insert_record('local_h5pca_book', $book);
        } else {
            $book->contentid = null;
            $book->contenthash = null;
            $book->manifesthash = null;
            $book->enabled = empty($record->enabled) ? 0 : 1;
            $book->defaultmessage = $this->clean_message($record->defaultmessage ?? null);
            $book->timemodified = $now;
            $DB->update_record('local_h5pca_book', $book);
        }

        $this->restoredbookid = (int) $book->id;
        $this->set_mapping('local_h5pca_book', $oldid, $this->restoredbookid);
    }

    /**
     * Restore one chapter policy by UUID.
     *
     * @param array $data Backup data
     */
    public function process_local_h5pchapteraccess_chapter(array $data): void {
        global $DB;

        if ($this->restoredbookid === null) {
            throw new restore_step_exception('local_h5pchapteraccess_book_not_restored');
        }

        $source = (object) $data;
        $oldid = (int) $source->id;
        $uuid = trim((string) $source->chapteruuid);
        if ($uuid === '' || \core_text::strlen($uuid) > 128) {
            throw new restore_step_exception('local_h5pchapteraccess_invalid_chapter_uuid');
        }

        $mode = in_array($source->accessmode ?? '', ['open', 'locked', 'conditional'], true)
            ? $source->accessmode
            : 'open';
        $now = time();
        $record = $DB->get_record('local_h5pca_chapter', [
            'bookid' => $this->restoredbookid,
            'chapteruuid' => $uuid,
        ]);
        if (!$record) {
            $record = (object) [
                'bookid' => $this->restoredbookid,
                'chapteruuid' => $uuid,
                'titlecache' => \core_text::substr(clean_param((string) ($source->titlecache ?? ''), PARAM_TEXT), 0, 255),
                'positioncache' => max(0, (int) ($source->positioncache ?? 0)),
                'stableid' => empty($source->stableid) ? 0 : 1,
                'accessmode' => $mode,
                'availabilityjson' => $this->clean_availability_json($source->availabilityjson ?? null),
                'lockedmessage' => $this->clean_message($source->lockedmessage ?? null),
                'showrestriction' => empty($source->showrestriction) ? 0 : 1,
                'active' => empty($source->active) ? 0 : 1,
                'timecreated' => $now,
                'timemodified' => $now,
            ];
            $record->id = $DB->insert_record('local_h5pca_chapter', $record);
        } else {
            $record->titlecache = \core_text::substr(
                clean_param((string) ($source->titlecache ?? ''), PARAM_TEXT),
                0,
                255
            );
            $record->positioncache = max(0, (int) ($source->positioncache ?? 0));
            $record->stableid = empty($source->stableid) ? 0 : 1;
            $record->accessmode = $mode;
            $record->availabilityjson = $this->clean_availability_json($source->availabilityjson ?? null);
            $record->lockedmessage = $this->clean_message($source->lockedmessage ?? null);
            $record->showrestriction = empty($source->showrestriction) ? 0 : 1;
            $record->active = empty($source->active) ? 0 : 1;
            $record->timemodified = $now;
            $DB->update_record('local_h5pca_chapter', $record);
        }

        $this->set_mapping('local_h5pca_chapter', $oldid, (int) $record->id);
    }

    /**
     * Remap Availability API references and reconcile the restored H5P package.
     */
    public function after_restore_module(): void {
        global $DB;

        $cmid = (int) $this->get_task()->get_moduleid();
        $courseid = (int) $this->get_task()->get_courseid();
        $book = $DB->get_record('local_h5pca_book', ['cmid' => $cmid]);
        if (!$book) {
            return;
        }

        rebuild_course_cache($courseid, true);
        $course = get_course($courseid);
        $cm = get_fast_modinfo($course)->get_cm($cmid);
        $dateoffset = $this->apply_date_offset(1) - 1;
        $chapters = $DB->get_records('local_h5pca_chapter', ['bookid' => $book->id]);

        foreach ($chapters as $chapter) {
            if (trim((string) ($chapter->availabilityjson ?? '')) === '') {
                continue;
            }
            try {
                $info = new chapter_info($course, $cm, $chapter);
                $info->update_after_restore(
                    $this->get_restoreid(),
                    $courseid,
                    $this->get_task()->get_logger(),
                    $dateoffset,
                    $this->get_task()
                );
            } catch (\coding_exception $exception) {
                $this->get_task()->get_logger()->process(
                    'Could not remap chapter availability for ' . $chapter->chapteruuid . ': ' .
                        $exception->getMessage(),
                    backup::LOG_WARNING
                );
            }
        }

        try {
            $cache = new manifest_cache();
            $cache->delete($cmid);
            $manifest = (new manifest_extractor(null, $cache))->extract_after_restore($cmid);
            (new manifest_synchronizer())->synchronize_after_restore($manifest);
        } catch (\Throwable $exception) {
            // The package can still be finalised by a later restore step. Normal
            // manage/policy access will retry the same hash-based synchronization.
            $this->get_task()->get_logger()->process(
                'H5P chapter manifest reconciliation was deferred: ' . $exception->getMessage(),
                backup::LOG_WARNING
            );
        }
    }

    /**
     * Clean one restored plain-text message.
     *
     * @param mixed $value Backup value
     * @return string|null
     */
    private function clean_message($value): ?string {
        $message = trim(clean_param((string) $value, PARAM_TEXT));
        return $message === '' ? null : $message;
    }

    /**
     * Preserve an Availability API tree for core to validate and remap.
     *
     * Invalid source JSON must remain invalid so the runtime evaluator fails
     * closed instead of accidentally treating a conditional chapter as open.
     *
     * @param mixed $value Backup value
     * @return string|null
     */
    private function clean_availability_json($value): ?string {
        $json = trim((string) $value);
        if ($json === '') {
            return null;
        }
        return $json;
    }
}
