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
use local_h5pchapteraccess\exception\invalid_activity_exception;
use local_h5pchapteraccess\service\configuration_service;
use local_h5pchapteraccess\service\manifest_extractor;

/**
 * Tests for authorization and manual chapter configuration.
 *
 * @package    local_h5pchapteraccess
 * @category   test
 * @copyright  2026 Luiz Gustavo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class configuration_service_test extends \advanced_testcase {

    /**
     * A user without the local manage capability cannot open the configuration.
     */
    public function test_require_manageable_activity_rejects_user_without_capability(): void {
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $activity = $this->getDataGenerator()->create_module('h5pactivity', ['course' => $course]);
        $student = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($student->id, $course->id, 'student');
        $this->setUser($student);

        $this->expectException(invalid_activity_exception::class);
        (new configuration_service())->require_manageable_activity((int) $activity->cmid);
    }

    /**
     * A nonexistent course module is rejected explicitly.
     */
    public function test_require_manageable_activity_rejects_missing_activity(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $this->expectException(invalid_activity_exception::class);
        (new configuration_service())->require_manageable_activity(99999999);
    }

    /**
     * A course module from another activity type is rejected.
     */
    public function test_require_manageable_activity_rejects_other_module(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $activity = $this->getDataGenerator()->create_module('page', ['course' => $course]);

        $this->expectException(invalid_activity_exception::class);
        (new configuration_service())->require_manageable_activity((int) $activity->cmid);
    }

    /**
     * Saving updates only editable stable chapters and leaves manifest caches untouched.
     */
    public function test_save_persists_activity_and_chapter_settings(): void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();
        $cmid = $this->create_h5p_activity_cmid();
        $service = new configuration_service();
        $service->synchronize_manifest($this->manifest($cmid, [
            new chapter('uuid-stable', 'Stable title', 0, true),
            new chapter('legacy-position-1', 'Legacy title', 1, false),
        ]));

        $book = $DB->get_record('local_h5pca_book', ['cmid' => $cmid], '*', MUST_EXIST);
        $stable = $DB->get_record(
            'local_h5pca_chapter',
            ['bookid' => $book->id, 'chapteruuid' => 'uuid-stable'],
            '*',
            MUST_EXIST
        );
        $legacy = $DB->get_record(
            'local_h5pca_chapter',
            ['bookid' => $book->id, 'chapteruuid' => 'legacy-position-1'],
            '*',
            MUST_EXIST
        );

        $data = (object) [
            'enabled' => 0,
            'defaultmessage' => 'Default plain message',
            'accessmode_' . $stable->id => 'locked',
            'lockedmessage_' . $stable->id => 'Specific plain message',
            // Forged controls for an unstable ID must be ignored.
            'accessmode_' . $legacy->id => 'locked',
            'lockedmessage_' . $legacy->id => 'Must not persist',
        ];
        $service->save($cmid, $data);

        $savedbook = $DB->get_record('local_h5pca_book', ['id' => $book->id], '*', MUST_EXIST);
        $savedstable = $DB->get_record('local_h5pca_chapter', ['id' => $stable->id], '*', MUST_EXIST);
        $savedlegacy = $DB->get_record('local_h5pca_chapter', ['id' => $legacy->id], '*', MUST_EXIST);

        $this->assertSame(0, (int) $savedbook->enabled);
        $this->assertSame('Default plain message', $savedbook->defaultmessage);
        $this->assertSame('locked', $savedstable->accessmode);
        $this->assertSame('Specific plain message', $savedstable->lockedmessage);
        $this->assertSame('Stable title', $savedstable->titlecache);
        $this->assertSame(0, (int) $savedstable->positioncache);
        $this->assertSame('open', $savedlegacy->accessmode);
        $this->assertNull($savedlegacy->lockedmessage);
    }

    /**
     * A specific nonempty message wins; otherwise the activity default is used.
     */
    public function test_effective_message_uses_specific_then_default(): void {
        $this->resetAfterTest();
        $service = new configuration_service();
        $book = (object) ['defaultmessage' => 'Activity default'];

        $this->assertSame(
            'Chapter message',
            $service->get_effective_locked_message($book, (object) ['lockedmessage' => 'Chapter message'])
        );
        $this->assertSame(
            'Activity default',
            $service->get_effective_locked_message($book, (object) ['lockedmessage' => ''])
        );
    }

    /**
     * Reorganization refreshes title and position but preserves manual policy by UUID.
     */
    public function test_synchronization_after_reorganization_preserves_manual_settings(): void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();
        $cmid = $this->create_h5p_activity_cmid();
        $service = new configuration_service();
        $service->synchronize_manifest($this->manifest($cmid, [
            new chapter('uuid-a', 'First', 0, true),
            new chapter('uuid-b', 'Second', 1, true),
        ]));

        $configuration = $service->get_configuration($cmid);
        $chaptera = $DB->get_record(
            'local_h5pca_chapter',
            ['bookid' => $configuration->book->id, 'chapteruuid' => 'uuid-a'],
            '*',
            MUST_EXIST
        );
        $service->save($cmid, (object) [
            'enabled' => 1,
            'defaultmessage' => '',
            'accessmode_' . $chaptera->id => 'locked',
            'lockedmessage_' . $chaptera->id => 'Preserved',
        ]);

        $service->synchronize_manifest($this->manifest($cmid, [
            new chapter('uuid-b', 'Second updated', 0, true),
            new chapter('uuid-a', 'First updated', 1, true),
        ], 102, 'hash-2'));

        $saved = $DB->get_record('local_h5pca_chapter', ['id' => $chaptera->id], '*', MUST_EXIST);
        $this->assertSame('locked', $saved->accessmode);
        $this->assertSame('Preserved', $saved->lockedmessage);
        $this->assertSame('First updated', $saved->titlecache);
        $this->assertSame(1, (int) $saved->positioncache);
    }

    /**
     * Create an H5P activity and return its course module ID.
     *
     * @return int
     */
    private function create_h5p_activity_cmid(): int {
        $course = $this->getDataGenerator()->create_course();
        $activity = $this->getDataGenerator()->create_module('h5pactivity', ['course' => $course]);
        return (int) $activity->cmid;
    }

    /**
     * Build a synthetic supported manifest.
     *
     * @param int $cmid Course module ID
     * @param chapter[] $chapters Chapter entries
     * @param int $contentid core_h5p content ID
     * @param string $contenthash Content hash
     * @return manifest
     */
    private function manifest(
        int $cmid,
        array $chapters,
        int $contentid = 101,
        string $contenthash = 'hash-1'
    ): manifest {
        return new manifest(
            $cmid,
            manifest_extractor::MACHINE_NAME,
            $contentid,
            $contenthash,
            $chapters
        );
    }
}
