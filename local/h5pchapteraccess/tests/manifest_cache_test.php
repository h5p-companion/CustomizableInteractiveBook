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
 * Tests for the user-independent structural manifest cache.
 *
 * @package    local_h5pchapteraccess
 * @category   test
 * @copyright  2026 Luiz Gustavo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class manifest_cache_test extends \advanced_testcase {

    /** A matching content hash restores chapters but uses the current content ID. */
    public function test_cache_hit_requires_current_content_hash(): void {
        $this->resetAfterTest();
        $cache = new manifest_cache();
        $cache->purge();
        $manifest = new manifest(123, manifest_extractor::MACHINE_NAME, 10, 'hash-a', [
            new chapter('uuid-a', 'First', 0, true),
        ]);
        $cache->set($manifest);

        $hit = $cache->get(123, 99, 'hash-a', manifest_extractor::MACHINE_NAME, [
            'major' => 1,
            'minor' => 0,
            'patch' => 35,
        ]);
        $this->assertNotNull($hit);
        $this->assertSame(99, $hit->get_content_id());
        $this->assertSame('uuid-a', $hit->get_chapters()[0]->get_id());
        $this->assertSame(
            ['major' => 1, 'minor' => 0, 'patch' => 35],
            $hit->get_library_version()
        );

        $this->assertNull($cache->get(123, 99, 'hash-b', manifest_extractor::MACHINE_NAME));
        $this->assertNull($cache->get(123, 99, 'hash-a', manifest_extractor::MACHINE_NAME));
    }

    /** Explicit invalidation removes one cached activity. */
    public function test_delete_invalidates_one_activity(): void {
        $this->resetAfterTest();
        $cache = new manifest_cache();
        $cache->purge();
        $cache->set(new manifest(456, manifest_extractor::MACHINE_NAME, 10, 'hash', [
            new chapter('uuid-a', 'First', 0, true),
        ]));

        $cache->delete(456);

        $this->assertNull($cache->get(456, 10, 'hash', manifest_extractor::MACHINE_NAME));
    }
}
