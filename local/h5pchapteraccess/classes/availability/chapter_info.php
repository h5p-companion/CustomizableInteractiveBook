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

namespace local_h5pchapteraccess\availability;

/**
 * Adapts a stored H5P chapter to Moodle's Availability API.
 *
 * @package    local_h5pchapteraccess
 * @copyright  2026 Luiz Gustavo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class chapter_info extends \core_availability\info {

    /** @var \cm_info Owning H5P activity. */
    private \cm_info $cm;

    /** @var \stdClass Stored chapter record. */
    private \stdClass $chapter;

    /**
     * Constructor.
     *
     * Chapters do not have Moodle's separate eye visibility flag, so they are
     * always passed to the parent as visible. Access is controlled exclusively
     * by their availability tree.
     *
     * @param \stdClass $course Course record
     * @param \cm_info $cm Owning H5P activity
     * @param \stdClass $chapter Stored chapter record
     */
    public function __construct(\stdClass $course, \cm_info $cm, \stdClass $chapter) {
        if (empty($chapter->id) || empty($chapter->bookid)) {
            throw new \coding_exception('A persisted chapter record is required for availability evaluation.');
        }

        $availability = trim((string) ($chapter->availabilityjson ?? ''));
        parent::__construct($course, true, $availability === '' ? null : $availability);

        $this->cm = $cm;
        $this->chapter = clone $chapter;
    }

    /**
     * Return the module context inherited by the chapter.
     *
     * @return \context_module
     */
    public function get_context() {
        return \context_module::instance($this->cm->id);
    }

    /**
     * Return a safe label used by Availability API diagnostics.
     *
     * @return string
     */
    protected function get_thing_name() {
        $title = trim((string) ($this->chapter->titlecache ?? ''));
        if ($title === '') {
            $title = clean_param((string) ($this->chapter->chapteruuid ?? ''), PARAM_TEXT);
        } else {
            $title = format_string($title, true, ['context' => $this->get_context()]);
        }

        return get_string('chapteravailabilityname', 'local_h5pchapteraccess', $title);
    }

    /**
     * Persist a tree changed by an explicit Availability API maintenance action.
     *
     * Evaluation never calls this method. The compound conditions ensure a
     * restore or dependency update cannot write to a different chapter.
     *
     * @param string|null $availability New availability JSON
     */
    protected function set_in_database($availability) {
        global $DB;

        $DB->set_field('local_h5pca_chapter', 'availabilityjson', $availability, [
            'id' => $this->chapter->id,
            'bookid' => $this->chapter->bookid,
            'chapteruuid' => $this->chapter->chapteruuid,
        ]);
    }

    /**
     * Capability used when Availability API filters user lists.
     *
     * Runtime bypass is also checked explicitly by availability_evaluator so
     * single-user checks have the same plugin-specific semantics.
     *
     * @return string
     */
    protected function get_view_hidden_capability() {
        return 'local/h5pchapteraccess:viewlocked';
    }
}
