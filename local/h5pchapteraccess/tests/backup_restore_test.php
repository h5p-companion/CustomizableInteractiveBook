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

/**
 * Integration tests for Moodle's official local-plugin backup connection point.
 *
 * @package    local_h5pchapteraccess
 * @category   test
 * @copyright  2026 Luiz Gustavo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class backup_restore_test extends \advanced_testcase {

    /**
     * Activity duplication uses a new cmid and preserves rules by UUID.
     */
    public function test_activity_duplication_copies_configuration_without_source_link(): void {
        global $DB;
        $this->resetAfterTest(true);
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $activity = $this->getDataGenerator()->create_module('h5pactivity', [
            'course' => $course,
            'name' => 'Book to duplicate',
        ]);
        $sourcebookid = $this->insert_configuration((int) $activity->cmid, null);

        $newcmid = $this->duplicate_activity($course, (int) $activity->cmid);

        $this->assertNotSame((int) $activity->cmid, $newcmid);
        $restoredbook = $DB->get_record('local_h5pca_book', ['cmid' => $newcmid], '*', MUST_EXIST);
        $this->assertNotSame($sourcebookid, (int) $restoredbook->id);
        $this->assertSame(0, (int) $restoredbook->enabled);
        $this->assertSame('Default message', $restoredbook->defaultmessage);
        $restoredchapter = $DB->get_record('local_h5pca_chapter', [
            'bookid' => $restoredbook->id,
            'chapteruuid' => 'uuid-a',
        ], '*', MUST_EXIST);
        $this->assertSame('conditional', $restoredchapter->accessmode);
        $this->assertSame('Specific message', $restoredchapter->lockedmessage);
        $this->assertSame(0, (int) $restoredchapter->showrestriction);
    }

    /**
     * Full course restore remaps completion references and shifts dates through core availability.
     */
    public function test_course_restore_remaps_availability_references_and_dates(): void {
        global $DB;
        $this->resetAfterTest(true);
        $this->setAdminUser();
        $startdate = usergetmidnight(time() - DAYSECS * 10);
        $course = $this->getDataGenerator()->create_course([
            'enablecompletion' => 1,
            'startdate' => $startdate,
        ]);
        $page = $this->getDataGenerator()->create_module('page', [
            'course' => $course,
            'name' => 'Required page',
            'completion' => COMPLETION_TRACKING_MANUAL,
        ]);
        $activity = $this->getDataGenerator()->create_module('h5pactivity', [
            'course' => $course,
            'name' => 'Book with conditions',
        ]);
        $conditiontime = $startdate + DAYSECS * 2;
        $availability = json_encode([
            'op' => '&',
            'c' => [
                ['type' => 'completion', 'cm' => (int) $page->cmid, 'e' => 1],
                ['type' => 'date', 'd' => '>=', 't' => $conditiontime],
            ],
            'showc' => [true, true],
        ], JSON_UNESCAPED_SLASHES);
        $this->insert_configuration((int) $activity->cmid, $availability);
        $newstartdate = $startdate + DAYSECS * 5;

        $newcourseid = $this->backup_and_restore_course($course, $newstartdate);

        $newh5p = get_coursemodule_from_instance(
            'h5pactivity',
            (int) $DB->get_field('h5pactivity', 'id', [
                'course' => $newcourseid,
                'name' => 'Book with conditions',
            ], MUST_EXIST),
            $newcourseid,
            false,
            MUST_EXIST
        );
        $newpage = get_coursemodule_from_instance(
            'page',
            (int) $DB->get_field('page', 'id', [
                'course' => $newcourseid,
                'name' => 'Required page',
            ], MUST_EXIST),
            $newcourseid,
            false,
            MUST_EXIST
        );
        $newbook = $DB->get_record('local_h5pca_book', ['cmid' => $newh5p->id], '*', MUST_EXIST);
        $newchapter = $DB->get_record('local_h5pca_chapter', [
            'bookid' => $newbook->id,
            'chapteruuid' => 'uuid-a',
        ], '*', MUST_EXIST);
        $decoded = json_decode($newchapter->availabilityjson, true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame((int) $newpage->id, (int) $decoded['c'][0]['cm']);
        $this->assertNotSame((int) $page->cmid, (int) $decoded['c'][0]['cm']);
        $this->assertSame($conditiontime + DAYSECS * 5, (int) $decoded['c'][1]['t']);
        $this->assertSame((int) $newh5p->id, (int) $newbook->cmid);
        $this->assertNotSame((int) $activity->cmid, (int) $newbook->cmid);
    }

    /**
     * Insert a configured book and chapter.
     *
     * @param int $cmid Course module ID
     * @param string|null $availability Availability tree
     * @return int Book ID
     */
    private function insert_configuration(int $cmid, ?string $availability): int {
        global $DB;
        $now = time();
        $bookid = $DB->insert_record('local_h5pca_book', (object) [
            'cmid' => $cmid,
            'contentid' => 987,
            'contenthash' => 'source-content-hash',
            'manifesthash' => 'source-manifest-hash',
            'enabled' => 0,
            'defaultmessage' => 'Default message',
            'timecreated' => $now,
            'timemodified' => $now,
        ]);
        $DB->insert_record('local_h5pca_chapter', (object) [
            'bookid' => $bookid,
            'chapteruuid' => 'uuid-a',
            'titlecache' => 'Configured chapter',
            'positioncache' => 0,
            'stableid' => 1,
            'accessmode' => 'conditional',
            'availabilityjson' => $availability,
            'lockedmessage' => 'Specific message',
            'showrestriction' => 0,
            'active' => 1,
            'timecreated' => $now,
            'timemodified' => $now,
        ]);
        return (int) $bookid;
    }

    /**
     * Duplicate one activity using the same flow as Moodle's duplicate action.
     *
     * @param \stdClass $course Course record
     * @param int $cmid Source course module ID
     * @return int New course module ID
     */
    private function duplicate_activity(\stdClass $course, int $cmid): int {
        global $CFG, $USER;
        require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');
        require_once($CFG->dirroot . '/backup/util/includes/restore_includes.php');

        $bc = new \backup_controller(
            \backup::TYPE_1ACTIVITY,
            $cmid,
            \backup::FORMAT_MOODLE,
            \backup::INTERACTIVE_NO,
            \backup::MODE_IMPORT,
            $USER->id
        );
        $backupid = $bc->get_backupid();
        $bc->execute_plan();
        $bc->destroy();

        $rc = new \restore_controller(
            $backupid,
            $course->id,
            \backup::INTERACTIVE_NO,
            \backup::MODE_IMPORT,
            $USER->id,
            \backup::TARGET_CURRENT_ADDING
        );
        $this->assertTrue($rc->execute_precheck());
        $rc->execute_plan();
        $newcmid = 0;
        foreach ($rc->get_plan()->get_tasks() as $task) {
            if ($task instanceof \restore_activity_task) {
                $newcmid = (int) $task->get_moduleid();
                break;
            }
        }
        $rc->destroy();
        $this->assertGreaterThan(0, $newcmid);
        return $newcmid;
    }

    /**
     * Back up and restore a complete course with a new start date.
     *
     * @param \stdClass $course Source course
     * @param int $newstartdate Restored course start date
     * @return int New course ID
     */
    private function backup_and_restore_course(\stdClass $course, int $newstartdate): int {
        global $CFG, $USER;
        require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');
        require_once($CFG->dirroot . '/backup/util/includes/restore_includes.php');

        $CFG->backup_file_logger_level = \backup::LOG_NONE;
        $bc = new \backup_controller(
            \backup::TYPE_1COURSE,
            $course->id,
            \backup::FORMAT_MOODLE,
            \backup::INTERACTIVE_NO,
            \backup::MODE_IMPORT,
            $USER->id
        );
        $backupid = $bc->get_backupid();
        $bc->execute_plan();
        $bc->destroy();

        $newcourseid = \restore_dbops::create_new_course(
            $course->fullname,
            $course->shortname . '_restored',
            $course->category
        );
        $rc = new \restore_controller(
            $backupid,
            $newcourseid,
            \backup::INTERACTIVE_NO,
            \backup::MODE_GENERAL,
            $USER->id,
            \backup::TARGET_NEW_COURSE
        );
        $rc->get_plan()->get_setting('course_startdate')->set_value($newstartdate);
        $this->assertTrue($rc->execute_precheck());
        $rc->execute_plan();
        $rc->destroy();
        return (int) $newcourseid;
    }
}
