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

use local_h5pchapteraccess\exception\unsupported_content_exception;
use local_h5pchapteraccess\service\manifest_extractor;

/**
 * Tests for deterministic chapter manifest parsing.
 *
 * @package    local_h5pchapteraccess
 * @category   test
 * @copyright  2026 Luiz Gustavo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class manifest_extractor_test extends \basic_testcase {

    /**
     * A valid book produces stable ordered entries and a deterministic hash.
     */
    public function test_valid_manifest(): void {
        $json = $this->book_json([
            $this->chapter_data('uuid-a', 'First'),
            $this->chapter_data('uuid-b', 'Second'),
        ]);
        $extractor = new manifest_extractor();

        $manifest = $extractor->create_manifest_from_json(
            10,
            20,
            'content-hash',
            manifest_extractor::MACHINE_NAME,
            $json
        );
        $same = $extractor->create_manifest_from_json(
            10,
            20,
            'content-hash',
            manifest_extractor::MACHINE_NAME,
            $json
        );

        $chapters = $manifest->get_chapters();
        $this->assertCount(2, $chapters);
        $this->assertSame('uuid-a', $chapters[0]->get_id());
        $this->assertSame('First', $chapters[0]->get_title());
        $this->assertSame(0, $chapters[0]->get_position());
        $this->assertTrue($chapters[0]->is_stable());
        $this->assertSame($manifest->get_manifest_hash(), $same->get_manifest_hash());
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $manifest->get_manifest_hash());
    }

    /**
     * The historical config.chapters wrapper remains supported.
     */
    public function test_config_chapters_wrapper_remains_supported(): void {
        $json = json_encode((object) [
            'config' => (object) [
                'chapters' => [$this->chapter_data('uuid-a', 'Wrapped')],
            ],
        ], JSON_THROW_ON_ERROR);

        $manifest = (new manifest_extractor())->create_manifest_from_json(
            10,
            20,
            'content-hash',
            manifest_extractor::MACHINE_NAME,
            $json
        );

        $this->assertSame('uuid-a', $manifest->get_chapters()[0]->get_id());
        $this->assertSame('Wrapped', $manifest->get_chapters()[0]->get_title());
    }

    /**
     * A different main library is rejected.
     */
    public function test_incorrect_library(): void {
        $this->expectException(unsupported_content_exception::class);
        $this->expectExceptionMessage('H5P.OtherLibrary');

        (new manifest_extractor())->create_manifest_from_json(
            10,
            20,
            'content-hash',
            'H5P.OtherLibrary',
            $this->book_json([$this->chapter_data('uuid-a', 'First')])
        );
    }

    /**
     * Invalid JSON is reported as unsupported content.
     */
    public function test_invalid_json(): void {
        $this->expectException(unsupported_content_exception::class);

        (new manifest_extractor())->create_manifest_from_json(
            10,
            20,
            'content-hash',
            manifest_extractor::MACHINE_NAME,
            '{invalid'
        );
    }

    /**
     * Missing chapter title gets the same deterministic fallback used by the H5P library.
     */
    public function test_chapter_without_title(): void {
        $manifest = (new manifest_extractor())->create_manifest_from_json(
            10,
            20,
            'content-hash',
            manifest_extractor::MACHINE_NAME,
            $this->book_json([(object) ['subContentId' => 'uuid-a']])
        );

        $this->assertSame(manifest_extractor::FALLBACK_TITLE, $manifest->get_chapters()[0]->get_title());
    }

    /**
     * Missing subContentId gets a controlled unstable runtime identifier.
     */
    public function test_chapter_without_subcontentid(): void {
        $manifest = (new manifest_extractor())->create_manifest_from_json(
            10,
            20,
            'content-hash',
            manifest_extractor::MACHINE_NAME,
            $this->book_json([(object) ['metadata' => (object) ['title' => 'Legacy']]])
        );

        $chapter = $manifest->get_chapters()[0];
        $this->assertSame('legacy-position-0', $chapter->get_id());
        $this->assertFalse($chapter->is_stable());
    }

    /**
     * Duplicate stable UUIDs are rejected before persistence.
     */
    public function test_duplicate_uuid(): void {
        $this->expectException(unsupported_content_exception::class);

        (new manifest_extractor())->create_manifest_from_json(
            10,
            20,
            'content-hash',
            manifest_extractor::MACHINE_NAME,
            $this->book_json([
                $this->chapter_data('duplicate', 'First'),
                $this->chapter_data('duplicate', 'Second'),
            ])
        );
    }

    /**
     * Create one H5P chapter parameters object.
     *
     * @param string $id subContentId
     * @param string $title Metadata title
     * @return \stdClass
     */
    private function chapter_data(string $id, string $title): \stdClass {
        return (object) [
            'subContentId' => $id,
            'metadata' => (object) ['title' => $title],
        ];
    }

    /**
     * Encode a minimal Customizable Interactive Book parameters payload.
     *
     * @param array $chapters Chapter objects
     * @return string
     */
    private function book_json(array $chapters): string {
        return json_encode((object) [
            'chapters' => $chapters,
        ], JSON_THROW_ON_ERROR);
    }
}
