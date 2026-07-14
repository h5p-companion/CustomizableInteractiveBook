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
use local_h5pchapteraccess\repository\book_repository;
use local_h5pchapteraccess\repository\chapter_repository;
use local_h5pchapteraccess\service\manifest_extractor;

/**
 * Tests repository persistence boundaries independently from synchronization.
 *
 * @package    local_h5pchapteraccess
 * @category   test
 * @copyright  2026 Luiz Gustavo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class repository_test extends \advanced_testcase {

    /** Book refresh updates identity fields without overwriting configuration. */
    public function test_book_repository_preserves_policy_configuration(): void {
        global $DB;
        $this->resetAfterTest();
        $cmid = $this->create_h5p_activity_cmid();
        $repository = new book_repository();
        $first = $repository->save_manifest($this->manifest($cmid, 10, 'hash-a', 'First'));
        $first->enabled = 0;
        $first->defaultmessage = 'Preserve me';
        $DB->update_record('local_h5pca_book', $first);

        $updated = $repository->save_manifest($this->manifest($cmid, 11, 'hash-b', 'Renamed'));

        $this->assertSame((int) $first->id, (int) $updated->id);
        $this->assertSame(11, (int) $updated->contentid);
        $this->assertSame('hash-b', $updated->contenthash);
        $this->assertSame(0, (int) $updated->enabled);
        $this->assertSame('Preserve me', $updated->defaultmessage);
    }

    /** Chapter refresh and deactivation preserve access fields by UUID. */
    public function test_chapter_repository_preserves_access_fields(): void {
        global $DB;
        $this->resetAfterTest();
        $cmid = $this->create_h5p_activity_cmid();
        $book = (new book_repository())->save_manifest($this->manifest($cmid, 10, 'hash-a', 'First'));
        $repository = new chapter_repository();
        $record = $repository->create((int) $book->id, new chapter('uuid-a', 'First', 0, true));
        $record->accessmode = 'locked';
        $record->availabilityjson = '{"op":"&","c":[],"showc":[]}';
        $record->lockedmessage = 'Preserve me';
        $DB->update_record('local_h5pca_chapter', $record);

        $this->assertTrue($repository->refresh($record, new chapter('uuid-a', 'Renamed', 2, true)));
        $this->assertTrue($repository->deactivate($record));
        $saved = $DB->get_record('local_h5pca_chapter', ['id' => $record->id], '*', MUST_EXIST);

        $this->assertSame('Renamed', $saved->titlecache);
        $this->assertSame(2, (int) $saved->positioncache);
        $this->assertSame('locked', $saved->accessmode);
        $this->assertSame('Preserve me', $saved->lockedmessage);
        $this->assertSame(0, (int) $saved->active);
    }

    /** @return int Course module ID. */
    private function create_h5p_activity_cmid(): int {
        $course = $this->getDataGenerator()->create_course();
        return (int) $this->getDataGenerator()->create_module('h5pactivity', ['course' => $course])->cmid;
    }

    /**
     * @param int $cmid Course module ID
     * @param int $contentid Content ID
     * @param string $hash Content hash
     * @param string $title Chapter title
     * @return manifest
     */
    private function manifest(int $cmid, int $contentid, string $hash, string $title): manifest {
        return new manifest($cmid, manifest_extractor::MACHINE_NAME, $contentid, $hash, [
            new chapter('uuid-a', $title, 0, true),
        ]);
    }
}
