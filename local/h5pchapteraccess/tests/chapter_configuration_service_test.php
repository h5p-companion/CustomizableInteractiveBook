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
use local_h5pchapteraccess\service\availability_evaluator;
use local_h5pchapteraccess\service\chapter_configuration_service;
use local_h5pchapteraccess\service\manifest_extractor;
use local_h5pchapteraccess\service\manifest_synchronizer;

/**
 * Tests for secure chapter condition configuration persistence.
 *
 * @package    local_h5pchapteraccess
 * @category   test
 * @copyright  2026 Luiz Gustavo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class chapter_configuration_service_test extends \advanced_testcase {

    /**
     * Conditional settings are stored without changing manifest cache fields.
     */
    public function test_save_persists_conditional_settings(): void {
        global $DB;
        [$course, $cm, $manifest, $record] = $this->create_fixture();
        $service = new chapter_configuration_service();
        $json = $this->date_json(time() + DAYSECS);

        $service->save($cm->id, 'uuid-a', (object) [
            'cmid' => $cm->id,
            'chapter' => 'uuid-a',
            'accessmode' => 'conditional',
            'lockedmessage' => '<b>Plain message</b>',
            'showrestriction' => 0,
            'availabilityconditionsjson' => $json,
        ]);

        $saved = $DB->get_record('local_h5pca_chapter', ['id' => $record->id], '*', MUST_EXIST);
        $this->assertSame('conditional', $saved->accessmode);
        $this->assertSame('Plain message', $saved->lockedmessage);
        $this->assertSame(0, (int) $saved->showrestriction);
        $this->assertSame($json, $saved->availabilityjson);
        $this->assertSame('Chapter A', $saved->titlecache);
        $this->assertSame(0, (int) $saved->positioncache);
    }

    /**
     * Switching to open retains the configured condition tree for later reuse.
     */
    public function test_open_mode_preserves_submitted_condition_tree(): void {
        global $DB;
        [$course, $cm, $manifest, $record] = $this->create_fixture();
        $service = new chapter_configuration_service();
        $json = $this->date_json(time() + DAYSECS);

        $service->save($cm->id, 'uuid-a', (object) [
            'cmid' => $cm->id,
            'chapter' => 'uuid-a',
            'accessmode' => 'open',
            'lockedmessage' => '',
            'showrestriction' => 1,
            'availabilityconditionsjson' => $json,
        ]);

        $saved = $DB->get_record('local_h5pca_chapter', ['id' => $record->id], '*', MUST_EXIST);
        $this->assertSame('open', $saved->accessmode);
        $this->assertSame($json, $saved->availabilityjson);
    }

    /**
     * A chapter from another activity cannot be selected by its UUID alone.
     */
    public function test_require_editable_chapter_confirms_cmid_and_manifest_membership(): void {
        [$course, $cm, $manifest] = $this->create_fixture();
        $othercourse = $this->getDataGenerator()->create_course();
        $otheractivity = $this->getDataGenerator()->create_module('h5pactivity', ['course' => $othercourse]);
        $othermanifest = $this->manifest((int) $otheractivity->cmid, 'other-uuid');
        (new manifest_synchronizer())->synchronize($othermanifest);

        $this->expectException(\moodle_exception::class);
        (new chapter_configuration_service())->require_editable_chapter(
            $cm->id,
            'other-uuid',
            $manifest
        );
    }

    /**
     * Inactive chapters are rejected from the normal editor.
     */
    public function test_inactive_chapter_is_rejected(): void {
        global $DB;
        [$course, $cm, $manifest, $record] = $this->create_fixture();
        $DB->set_field('local_h5pca_chapter', 'active', 0, ['id' => $record->id]);

        $this->expectException(\moodle_exception::class);
        (new chapter_configuration_service())->require_editable_chapter($cm->id, 'uuid-a', $manifest);
    }

    /**
     * Malformed JSON is rejected before persistence.
     */
    public function test_invalid_availability_json_is_rejected(): void {
        [$course, $cm] = $this->create_fixture();

        $this->expectException(\invalid_parameter_exception::class);
        (new chapter_configuration_service())->save($cm->id, 'uuid-a', (object) [
            'cmid' => $cm->id,
            'chapter' => 'uuid-a',
            'accessmode' => 'conditional',
            'lockedmessage' => '',
            'showrestriction' => 1,
            'availabilityconditionsjson' => '{invalid',
        ]);
    }

    /**
     * Changing a condition changes the decision after records are reloaded.
     */
    public function test_changed_condition_is_used_after_reload(): void {
        global $DB;
        [$course, $cm, $manifest, $record] = $this->create_fixture();
        $service = new chapter_configuration_service();
        $student = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($student->id, $course->id, 'student');

        $service->save($cm->id, 'uuid-a', (object) [
            'cmid' => $cm->id,
            'chapter' => 'uuid-a',
            'accessmode' => 'conditional',
            'lockedmessage' => '',
            'showrestriction' => 1,
            'availabilityconditionsjson' => $this->date_json(time() + DAYSECS),
        ]);
        $stored = $DB->get_record('local_h5pca_chapter', ['id' => $record->id], '*', MUST_EXIST);
        $this->assertFalse((new availability_evaluator())->evaluate(
            $stored,
            $course,
            $cm,
            $student->id
        )['available']);

        $service->save($cm->id, 'uuid-a', (object) [
            'cmid' => $cm->id,
            'chapter' => 'uuid-a',
            'accessmode' => 'conditional',
            'lockedmessage' => '',
            'showrestriction' => 1,
            'availabilityconditionsjson' => $this->date_json(time() - DAYSECS),
        ]);
        $stored = $DB->get_record('local_h5pca_chapter', ['id' => $record->id], '*', MUST_EXIST);
        $this->assertTrue((new availability_evaluator())->evaluate(
            $stored,
            $course,
            $cm,
            $student->id
        )['available']);
    }

    /**
     * Reordering the manifest preserves the condition tree by UUID.
     */
    public function test_reorganization_preserves_conditions_by_uuid(): void {
        global $DB;
        [$course, $cm, $manifest, $record] = $this->create_fixture();
        $service = new chapter_configuration_service();
        $json = $this->date_json(time() + DAYSECS);
        $service->save($cm->id, 'uuid-a', (object) [
            'cmid' => $cm->id,
            'chapter' => 'uuid-a',
            'accessmode' => 'conditional',
            'lockedmessage' => 'Keep me',
            'showrestriction' => 1,
            'availabilityconditionsjson' => $json,
        ]);

        $reordered = new manifest(
            $cm->id,
            manifest_extractor::MACHINE_NAME,
            102,
            'content-hash-2',
            [
                new chapter('uuid-b', 'Chapter B', 0, true),
                new chapter('uuid-a', 'Chapter A moved', 1, true),
            ]
        );
        (new manifest_synchronizer())->synchronize($reordered);

        $saved = $DB->get_record('local_h5pca_chapter', ['id' => $record->id], '*', MUST_EXIST);
        $this->assertSame('conditional', $saved->accessmode);
        $this->assertSame($json, $saved->availabilityjson);
        $this->assertSame('Keep me', $saved->lockedmessage);
        $this->assertSame(1, (int) $saved->positioncache);
    }

    /**
     * Create a synchronized two-chapter H5P activity fixture.
     *
     * @return array Course, cm_info, manifest and chapter record
     */
    private function create_fixture(): array {
        global $CFG, $DB;

        $this->resetAfterTest();
        $CFG->enableavailability = 1;
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $activity = $this->getDataGenerator()->create_module('h5pactivity', ['course' => $course]);
        $cm = get_fast_modinfo($course)->get_cm($activity->cmid);
        $manifest = new manifest(
            $cm->id,
            manifest_extractor::MACHINE_NAME,
            101,
            'content-hash-1',
            [
                new chapter('uuid-a', 'Chapter A', 0, true),
                new chapter('uuid-b', 'Chapter B', 1, true),
            ]
        );
        (new manifest_synchronizer())->synchronize($manifest);
        $bookid = $DB->get_field('local_h5pca_book', 'id', ['cmid' => $cm->id], MUST_EXIST);
        $record = $DB->get_record('local_h5pca_chapter', [
            'bookid' => $bookid,
            'chapteruuid' => 'uuid-a',
        ], '*', MUST_EXIST);

        return [$course, $cm, $manifest, $record];
    }

    /**
     * Build a single-chapter manifest.
     *
     * @param int $cmid Course module ID
     * @param string $uuid Chapter UUID
     * @return manifest
     */
    private function manifest(int $cmid, string $uuid): manifest {
        return new manifest(
            $cmid,
            manifest_extractor::MACHINE_NAME,
            201,
            'other-content-hash',
            [new chapter($uuid, 'Other chapter', 0, true)]
        );
    }

    /**
     * Build a valid date condition tree.
     *
     * @param int $time Timestamp
     * @return string
     */
    private function date_json(int $time): string {
        return json_encode((object) [
            'op' => '&',
            'showc' => [true],
            'c' => [\availability_date\condition::get_json('>=', $time)],
        ], JSON_THROW_ON_ERROR);
    }
}
