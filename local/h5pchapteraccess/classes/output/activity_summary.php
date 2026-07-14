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

use local_h5pchapteraccess\dto\manifest;

/**
 * Template context for the activity and manifest summary.
 *
 * @package    local_h5pchapteraccess
 * @copyright  2026 Luiz Gustavo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class activity_summary implements \renderable, \templatable {

    /** @var \stdClass Activity record. */
    private \stdClass $activity;

    /** @var \context_module Activity context. */
    private \context_module $context;

    /** @var manifest Current manifest. */
    private manifest $manifest;

    /** @var \stdClass Book configuration record. */
    private \stdClass $book;

    /**
     * Constructor.
     *
     * @param \stdClass $activity h5pactivity record
     * @param \context_module $context Activity context
     * @param manifest $manifest Current manifest
     * @param \stdClass $book Book configuration record
     */
    public function __construct(
        \stdClass $activity,
        \context_module $context,
        manifest $manifest,
        \stdClass $book
    ) {
        $this->activity = $activity;
        $this->context = $context;
        $this->manifest = $manifest;
        $this->book = $book;
    }

    /**
     * Export safe scalar values for Mustache.
     *
     * @param \renderer_base $output Renderer
     * @return \stdClass
     */
    public function export_for_template(\renderer_base $output): \stdClass {
        return (object) [
            'activityname' => strip_tags(format_string(
                $this->activity->name,
                true,
                ['context' => $this->context]
            )),
            'cmid' => $this->manifest->get_cmid(),
            'contentid' => $this->manifest->get_content_id(),
            'contenthash' => $this->manifest->get_content_hash(),
            'manifesthash' => $this->manifest->get_manifest_hash(),
            'activecount' => count($this->manifest->get_chapters()),
            'integrationstatus' => get_string(
                (bool) $this->book->enabled ? 'statusenabled' : 'statusdisabled',
                'local_h5pchapteraccess'
            ),
            'studentviewnotice' => get_string('studentviewnotice', 'local_h5pchapteraccess'),
        ];
    }
}
