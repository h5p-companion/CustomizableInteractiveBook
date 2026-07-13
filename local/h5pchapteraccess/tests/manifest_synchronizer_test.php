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
use local_h5pchapteraccess\service\manifest_extractor;
use local_h5pchapteraccess\service\manifest_synchronizer;

/**
 * Tests for transactional chapter manifest synchronization.
 *
 * @package    local_h5pchapteraccess
 * @category   test
 * @copyright  2026 Luiz Gustavo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class manifest_synchronizer_test extends \advanced_testcase {

    /**
     * Reordering refreshes caches while preserving policy fields by UUID.
     */
    public function test_reorganization_preserves_configuration(): void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();
        $cmid = $this->create_h5p_activity_cmid();
        $synchronizer = new manifest_synchronizer();

        $first = $this->manifest($cmid, [
            new chapter('uuid-a', 'First', 0, true),
            new chapter('uuid-b', 'Second', 1, true),
        ]);
        $this->assertSame(
            ['created' => 2, 'updated' => 0, 'reactivated' => 0, 'deactivated' => 0],
            $synchronizer->synchronize($first)
        );

        $book = $DB->get_record('local_h5pca_book', ['cmid' => $cmid], '*', MUST_EXIST);
        $configured = $DB->get_record(
            'local_h5pca_chapter',
            ['bookid' => $book->id, 'chapteruuid' => 'uuid-a'],
            '*',
            MUST_EXIST
        );
        $configured->accessmode = 'conditional';
        $configured->availabilityjson = '{"op":"test"}';
        $configured->lockedmessage = 'Keep this message';
        $configured->showrestriction = 0;
        $DB->update_record('local_h5pca_chapter', $configured);

        $reordered = $this->manifest($cmid, [
            new chapter('uuid-b', 'Second updated', 0, true),
            new chapter('uuid-a', 'First updated', 1, true),
        ], 102, 'hash-2');
        $summary = $synchronizer->synchronize($reordered);

        $this->assertSame(2, $summary['updated']);
        $preserved = $DB->get_record(
            'local_h5pca_chapter',
            ['bookid' => $book->id, 'chapteruuid' => 'uuid-a'],
            '*',
            MUST_EXIST
        );
        $this->assertSame('conditional', $preserved->accessmode);
        $this->assertSame('{"op":"test"}', $preserved->availabilityjson);
        $this->assertSame('Keep this message', $preserved->lockedmessage);
        $this->assertSame(0, (int) $preserved->showrestriction);
        $this->assertSame('First updated', $preserved->titlecache);
        $this->assertSame(1, (int) $preserved->positioncache);
        $this->assertSame(102, (int) $DB->get_field('local_h5pca_book', 'contentid', ['id' => $book->id]));
    }

    /**
     * A removed UUID is retained but marked inactive.
     */
    public function test_removal_marks_chapter_inactive(): void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();
        $cmid = $this->create_h5p_activity_cmid();
        $synchronizer = new manifest_synchronizer();

        $synchronizer->synchronize($this->manifest($cmid, [
            new chapter('uuid-a', 'First', 0, true),
            new chapter('uuid-b', 'Second', 1, true),
        ]));
        $summary = $synchronizer->synchronize($this->manifest($cmid, [
            new chapter('uuid-a', 'First', 0, true),
        ], 102, 'hash-2'));

        $bookid = $DB->get_field('local_h5pca_book', 'id', ['cmid' => $cmid], MUST_EXIST);
        $removed = $DB->get_record(
            'local_h5pca_chapter',
            ['bookid' => $bookid, 'chapteruuid' => 'uuid-b'],
            '*',
            MUST_EXIST
        );
        $this->assertSame(1, $summary['deactivated']);
        $this->assertSame(0, (int) $removed->active);
    }

    /**
     * Reintroducing a UUID reactivates its preserved configuration.
     */
    public function test_reintroduction_reactivates_chapter(): void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();
        $cmid = $this->create_h5p_activity_cmid();
        $synchronizer = new manifest_synchronizer();

        $synchronizer->synchronize($this->manifest($cmid, [
            new chapter('uuid-a', 'First', 0, true),
            new chapter('uuid-b', 'Second', 1, true),
        ]));
        $synchronizer->synchronize($this->manifest($cmid, [
            new chapter('uuid-a', 'First', 0, true),
        ], 102, 'hash-2'));

        $bookid = $DB->get_field('local_h5pca_book', 'id', ['cmid' => $cmid], MUST_EXIST);
        $record = $DB->get_record(
            'local_h5pca_chapter',
            ['bookid' => $bookid, 'chapteruuid' => 'uuid-b'],
            '*',
            MUST_EXIST
        );
        $record->accessmode = 'locked';
        $record->lockedmessage = 'Still locked';
        $DB->update_record('local_h5pca_chapter', $record);

        $summary = $synchronizer->synchronize($this->manifest($cmid, [
            new chapter('uuid-a', 'First', 0, true),
            new chapter('uuid-b', 'Second restored', 1, true),
        ], 103, 'hash-3'));

        $reactivated = $DB->get_record('local_h5pca_chapter', ['id' => $record->id], '*', MUST_EXIST);
        $this->assertSame(1, $summary['reactivated']);
        $this->assertSame(1, (int) $reactivated->active);
        $this->assertSame('locked', $reactivated->accessmode);
        $this->assertSame('Still locked', $reactivated->lockedmessage);
        $this->assertSame('Second restored', $reactivated->titlecache);
    }

    /**
     * Create an activity and return its course module ID.
     *
     * @return int
     */
    private function create_h5p_activity_cmid(): int {
        $course = $this->getDataGenerator()->create_course();
        $activity = $this->getDataGenerator()->create_module('h5pactivity', ['course' => $course]);
        return (int) $activity->cmid;
    }

    /**
     * Build a manifest for synchronizer tests.
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
