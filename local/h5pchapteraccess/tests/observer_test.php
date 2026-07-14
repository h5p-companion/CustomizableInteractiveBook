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

namespace local_h5pchapteraccess;

use local_h5pchapteraccess\dto\chapter;
use local_h5pchapteraccess\dto\manifest;
use local_h5pchapteraccess\service\manifest_cache;
use local_h5pchapteraccess\service\manifest_extractor;

/**
 * Tests lifecycle cleanup observers using real Moodle deletion events.
 *
 * @package    local_h5pchapteraccess
 * @category   test
 * @copyright  2026 Luiz Gustavo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class observer_test extends \advanced_testcase {

    /** An H5P activity update invalidates its structural manifest cache. */
    public function test_course_module_updated_invalidates_manifest_cache(): void {
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $activity = $this->getDataGenerator()->create_module('h5pactivity', ['course' => $course]);
        $cm = get_coursemodule_from_id('h5pactivity', (int) $activity->cmid, 0, false, MUST_EXIST);
        $cache = new manifest_cache();
        $cache->purge();
        $cache->set(new manifest((int) $activity->cmid, manifest_extractor::MACHINE_NAME, 10, 'hash', [
            new chapter('uuid-a', 'Chapter', 0, true),
        ]));
        $event = \core\event\course_module_updated::create_from_cm($cm);

        observer::course_module_updated($event);

        $this->assertNull($cache->get(
            (int) $activity->cmid,
            10,
            'hash',
            manifest_extractor::MACHINE_NAME
        ));
    }

    /** Permanently deleting an H5P activity removes its plugin records. */
    public function test_course_module_deleted_removes_configuration(): void {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/course/lib.php');

        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $activity = $this->getDataGenerator()->create_module('h5pactivity', ['course' => $course]);
        $bookid = $this->insert_configuration((int) $activity->cmid);

        course_delete_module((int) $activity->cmid);

        $this->assertFalse($DB->record_exists('local_h5pca_book', ['id' => $bookid]));
        $this->assertFalse($DB->record_exists('local_h5pca_chapter', ['bookid' => $bookid]));
    }

    /** Course deletion also leaves no activity-scoped plugin records. */
    public function test_course_deleted_removes_configuration(): void {
        global $DB;
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $activity = $this->getDataGenerator()->create_module('h5pactivity', ['course' => $course]);
        $bookid = $this->insert_configuration((int) $activity->cmid);

        delete_course($course, false);

        $this->assertFalse($DB->record_exists('local_h5pca_book', ['id' => $bookid]));
        $this->assertFalse($DB->record_exists('local_h5pca_chapter', ['bookid' => $bookid]));
    }

    /**
     * Insert the minimum valid book and chapter records.
     *
     * @param int $cmid Course module ID
     * @return int Book ID
     */
    private function insert_configuration(int $cmid): int {
        global $DB;
        $now = time();
        $bookid = $DB->insert_record('local_h5pca_book', (object) [
            'cmid' => $cmid,
            'enabled' => 1,
            'timecreated' => $now,
            'timemodified' => $now,
        ]);
        $DB->insert_record('local_h5pca_chapter', (object) [
            'bookid' => $bookid,
            'chapteruuid' => 'uuid-a',
            'titlecache' => 'Chapter',
            'positioncache' => 0,
            'stableid' => 1,
            'accessmode' => 'locked',
            'showrestriction' => 1,
            'active' => 1,
            'timecreated' => $now,
            'timemodified' => $now,
        ]);
        return (int) $bookid;
    }
}
