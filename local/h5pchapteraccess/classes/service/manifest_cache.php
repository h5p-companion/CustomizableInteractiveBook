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

namespace local_h5pchapteraccess\service;

use local_h5pchapteraccess\dto\chapter;
use local_h5pchapteraccess\dto\manifest;

/**
 * Stores only user-independent chapter manifest data in Moodle's application cache.
 *
 * Policy results are deliberately excluded because availability can change with
 * the user, time, grades, groups and activity completion.
 *
 * @package    local_h5pchapteraccess
 * @copyright  2026 Luiz Gustavo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class manifest_cache {

    /** @var \core_cache\cache Manifest cache. */
    private \core_cache\cache $cache;

    /**
     * Constructor.
     *
     * @param \core_cache\cache|null $cache Cache instance, primarily for tests
     */
    public function __construct(?\core_cache\cache $cache = null) {
        $this->cache = $cache ?? \cache::make('local_h5pchapteraccess', 'manifest');
    }

    /**
     * Restore a cached manifest only when the current package hash still matches.
     *
     * The current content ID and machine name are supplied by core_h5p on every
     * request and are never trusted from the cached payload.
     *
     * @param int $cmid Course module ID
     * @param int $contentid Current core_h5p content ID
     * @param string $contenthash Current package content hash
     * @param string $machinename Current main library machine name
     * @return manifest|null
     */
    public function get(
        int $cmid,
        int $contentid,
        string $contenthash,
        string $machinename
    ): ?manifest {
        $encoded = $this->cache->get((string) $cmid);
        if (!is_string($encoded)) {
            return null;
        }

        try {
            $data = json_decode($encoded, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            $this->delete($cmid);
            return null;
        }

        if (!is_array($data)
                || ($data['contenthash'] ?? null) !== $contenthash
                || !is_array($data['chapters'] ?? null)) {
            $this->delete($cmid);
            return null;
        }

        try {
            $chapters = array_map(static function(array $item): chapter {
                return new chapter(
                    (string) ($item['id'] ?? ''),
                    (string) ($item['title'] ?? ''),
                    (int) ($item['position'] ?? -1),
                    (bool) ($item['stable'] ?? false)
                );
            }, $data['chapters']);

            return new manifest($cmid, $machinename, $contentid, $contenthash, $chapters);
        } catch (\Throwable $exception) {
            $this->delete($cmid);
            return null;
        }
    }

    /**
     * Cache the structural manifest.
     *
     * @param manifest $manifest Extracted manifest
     */
    public function set(manifest $manifest): void {
        $payload = [
            'contenthash' => $manifest->get_content_hash(),
            'chapters' => array_map(static fn(chapter $chapter): array => $chapter->to_array(),
                $manifest->get_chapters()),
        ];
        $this->cache->set((string) $manifest->get_cmid(), json_encode(
            $payload,
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
        ));
    }

    /**
     * Invalidate one activity manifest.
     *
     * @param int $cmid Course module ID
     */
    public function delete(int $cmid): void {
        $this->cache->delete((string) $cmid);
    }

    /** Remove all structural manifests. */
    public function purge(): void {
        $this->cache->purge();
    }
}
