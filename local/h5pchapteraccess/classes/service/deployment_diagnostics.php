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

use local_h5pchapteraccess\dto\manifest;

/**
 * Builds user-independent deployment and configuration diagnostics.
 *
 * @package    local_h5pchapteraccess
 * @copyright  2026 Luiz Gustavo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class deployment_diagnostics {

    /** First H5P library release containing host contract version 1. */
    public const MINIMUM_LIBRARY_VERSION = '1.0.32';

    /**
     * Inspect the artifacts and persisted configuration required for enforcement.
     *
     * This does not evaluate a user policy. It is safe to display on the teacher
     * configuration page and deliberately avoids caching availability decisions.
     *
     * @param manifest $manifest Trusted current manifest
     * @param \stdClass $book Activity configuration record
     * @param \stdClass[] $chapters Active chapter records
     * @param bool|null $bridgeassetavailable Test override for the generated AMD asset check
     * @return array Diagnostic scalar values
     */
    public function inspect(
        manifest $manifest,
        \stdClass $book,
        array $chapters,
        ?bool $bridgeassetavailable = null
    ): array {
        global $CFG;

        $version = $manifest->get_library_version();
        $versionlabel = $version === null ? '' : implode('.', [
            $version['major'],
            $version['minor'],
            $version['patch'],
        ]);
        $librarycompatible = $versionlabel !== ''
            && version_compare($versionlabel, self::MINIMUM_LIBRARY_VERSION, '>=');

        if ($bridgeassetavailable === null) {
            $bridgeassetavailable = is_readable(
                $CFG->dirroot . '/local/h5pchapteraccess/amd/build/bridge.min.js'
            );
        }

        $counts = [
            'open' => 0,
            'locked' => 0,
            'conditional' => 0,
            'unsupported' => 0,
            'unstable' => 0,
        ];
        foreach ($chapters as $chapter) {
            if (empty($chapter->active)) {
                continue;
            }
            $mode = (string) ($chapter->accessmode ?? '');
            if (array_key_exists($mode, $counts) && $mode !== 'unstable') {
                $counts[$mode]++;
            } else {
                $counts['unsupported']++;
            }
            if (empty($chapter->stableid)) {
                $counts['unstable']++;
            }
        }

        $restrictedcount = $counts['locked'] + $counts['conditional'];
        $integrationenabled = !empty($book->enabled);

        return [
            'libraryversion' => $versionlabel,
            'minimumlibraryversion' => self::MINIMUM_LIBRARY_VERSION,
            'librarycompatible' => $librarycompatible,
            'bridgeassetavailable' => $bridgeassetavailable,
            'integrationenabled' => $integrationenabled,
            'activecount' => count($manifest->get_chapters()),
            'opencount' => $counts['open'],
            'lockedcount' => $counts['locked'],
            'conditionalcount' => $counts['conditional'],
            'restrictedcount' => $restrictedcount,
            'unsupportedcount' => $counts['unsupported'],
            'unstablecount' => $counts['unstable'],
            'ready' => $librarycompatible
                && $bridgeassetavailable
                && $integrationenabled
                && $restrictedcount > 0,
        ];
    }
}
