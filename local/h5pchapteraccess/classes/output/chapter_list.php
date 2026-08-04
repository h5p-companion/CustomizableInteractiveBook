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
 * Teacher-facing list of active H5P chapters.
 *
 * @package    local_h5pchapteraccess
 * @copyright  2026 Luiz Gustavo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class chapter_list implements \renderable, \templatable {

    /** @var \stdClass[] Prepared active chapters. */
    private array $chapters;

    /** @var \context_module Activity context. */
    private \context_module $context;

    /** @var \moodle_url Management page URL. */
    private \moodle_url $pageurl;

    /** @var string Selected chapter UUID. */
    private string $selectedid;

    /**
     * Constructor.
     *
     * @param \stdClass[] $chapters Prepared chapters
     * @param \context_module $context Activity context
     * @param \moodle_url $pageurl Management page URL
     * @param string $selectedid Selected chapter UUID
     */
    public function __construct(
        array $chapters,
        \context_module $context,
        \moodle_url $pageurl,
        string $selectedid = ''
    ) {
        $this->chapters = $chapters;
        $this->context = $context;
        $this->pageurl = $pageurl;
        $this->selectedid = $selectedid;
    }

    /**
     * Export a concise and escaped list for Mustache.
     *
     * @param \renderer_base $output Renderer
     * @return \stdClass
     */
    public function export_for_template(\renderer_base $output): \stdClass {
        $items = [];
        foreach ($this->chapters as $chapter) {
            $mode = (string) $chapter->accessmode;
            $url = new \moodle_url($this->pageurl, ['chapter' => $chapter->chapteruuid]);
            $url->set_anchor('chapter-editor');
            $stable = !empty($chapter->stableid);

            $items[] = [
                'position' => (int) $chapter->positioncache + 1,
                'title' => strip_tags(format_string(
                    (string) $chapter->titlecache,
                    true,
                    ['context' => $this->context]
                )),
                'modelabel' => (string) $chapter->accessmodelabel,
                'modeclass' => $this->mode_class($mode),
                'isconditional' => $mode === 'conditional',
                'conditionsummaryhtml' => $chapter->conditionsummaryhtml,
                'stable' => $stable,
                'unstable' => !$stable,
                'configureurl' => $stable ? $url->out(false) : '',
                'selected' => hash_equals($this->selectedid, (string) $chapter->chapteruuid),
            ];
        }

        return (object) [
            'haschapters' => $items !== [],
            'chapters' => $items,
        ];
    }

    /**
     * Get the visual class for an access mode.
     *
     * @param string $mode Access mode
     * @return string
     */
    private function mode_class(string $mode): string {
        return match ($mode) {
            'open' => 'local-h5pca-mode-open',
            'locked' => 'local-h5pca-mode-locked',
            'conditional' => 'local-h5pca-mode-conditional',
            default => 'local-h5pca-mode-unknown',
        };
    }
}
