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

namespace local_h5pchapteraccess\output;

/**
 * Template context for inactive chapter diagnostics.
 *
 * @package    local_h5pchapteraccess
 * @copyright  2026 Luiz Gustavo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class inactive_chapters implements \renderable, \templatable {

    /** @var \stdClass[] Inactive chapter records. */
    private array $chapters;

    /**
     * Constructor.
     *
     * @param \stdClass[] $chapters Inactive chapter records
     */
    public function __construct(array $chapters) {
        $this->chapters = array_values($chapters);
    }

    /**
     * Export diagnostic values for Mustache.
     *
     * @param \renderer_base $output Renderer
     * @return \stdClass
     */
    public function export_for_template(\renderer_base $output): \stdClass {
        $chapters = [];
        foreach ($this->chapters as $chapter) {
            $chapters[] = (object) [
                'position' => (int) $chapter->positioncache + 1,
                'title' => (string) $chapter->titlecache,
                'uuid' => (string) $chapter->chapteruuid,
                'stable' => get_string(
                    (bool) $chapter->stableid ? 'stableidyes' : 'stableidno',
                    'local_h5pchapteraccess'
                ),
                'mode' => $this->mode_label((string) $chapter->accessmode),
            ];
        }

        return (object) [
            'haschapters' => $chapters !== [],
            'chapters' => $chapters,
            'heading' => get_string('inactivechapters', 'local_h5pchapteraccess'),
            'description' => get_string('inactivechaptersdescription', 'local_h5pchapteraccess'),
            'caption' => get_string('inactivechapterscaption', 'local_h5pchapteraccess'),
            'positionlabel' => get_string('position', 'local_h5pchapteraccess'),
            'titlelabel' => get_string('chaptertitle', 'local_h5pchapteraccess'),
            'uuidlabel' => get_string('chapteruuid', 'local_h5pchapteraccess'),
            'stablelabel' => get_string('stableid', 'local_h5pchapteraccess'),
            'modelabel' => get_string('accessmode', 'local_h5pchapteraccess'),
        ];
    }

    /**
     * Map only currently exposed modes to labels.
     *
     * @param string $mode Persisted mode
     * @return string
     */
    private function mode_label(string $mode): string {
        if ($mode === 'open') {
            return get_string('modeopen', 'local_h5pchapteraccess');
        }
        if ($mode === 'locked') {
            return get_string('modelocked', 'local_h5pchapteraccess');
        }
        return get_string('modeunavailable', 'local_h5pchapteraccess');
    }
}
