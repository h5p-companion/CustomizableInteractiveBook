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

use local_h5pchapteraccess\availability\chapter_info;

/**
 * Evaluates one stored chapter for one user.
 *
 * @package    local_h5pchapteraccess
 * @copyright  2026 Luiz Gustavo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class availability_evaluator {

    /**
     * Evaluate a chapter without changing its stored condition tree.
     *
     * @param \stdClass $chapter Stored chapter record
     * @param \stdClass $course Course record
     * @param \cm_info $cm Owning H5P activity
     * @param int $userid User to evaluate, which need not be the current user
     * @return array{available: bool, information: string, hasRestrictions: bool}
     */
    public function evaluate(\stdClass $chapter, \stdClass $course, \cm_info $cm, int $userid): array {
        $mode = (string) ($chapter->accessmode ?? 'locked');
        $availability = trim((string) ($chapter->availabilityjson ?? ''));
        $hasrestrictions = $mode === 'locked' || ($mode === 'conditional' && $availability !== '');
        $context = \context_module::instance($cm->id);

        if (has_capability('local/h5pchapteraccess:viewlocked', $context, $userid)) {
            return $this->result(true, '', $hasrestrictions);
        }

        if ($mode === 'open') {
            return $this->result(true, '', false);
        }

        if ($mode === 'locked') {
            return $this->result(false, '', true);
        }

        if ($mode !== 'conditional') {
            debugging(
                'Unknown chapter access mode for local_h5pchapteraccess chapter record ' . (int) ($chapter->id ?? 0),
                DEBUG_DEVELOPER
            );
            return $this->result(false, get_string('availabilityinvalid', 'local_h5pchapteraccess'), true);
        }

        if ($availability === '') {
            return $this->result(
                true,
                get_string('conditionalwithoutconditions', 'local_h5pchapteraccess'),
                false
            );
        }

        $info = new chapter_info($course, $cm, $chapter);
        try {
            // Decode first so malformed trees can be distinguished from valid,
            // intentionally hidden condition descriptions.
            $info->get_availability_tree();
        } catch (\coding_exception $exception) {
            debugging(
                'Invalid availability JSON for local_h5pchapteraccess chapter record '
                    . (int) $chapter->id . ': ' . $exception->getMessage(),
                DEBUG_DEVELOPER
            );
            return $this->result(false, get_string('availabilityinvalid', 'local_h5pchapteraccess'), true);
        }

        $information = '';
        $available = $info->is_available($information, false, $userid);
        if (empty($chapter->showrestriction)) {
            $information = '';
        } else {
            $information = $this->to_plain_text($information, $course);
        }

        return $this->result($available, $information, true);
    }

    /**
     * Convert Availability API display HTML to one plain-text message.
     *
     * @param mixed $information Availability API information
     * @param \stdClass $course Course record
     * @return string
     */
    private function to_plain_text($information, \stdClass $course): string {
        if ($information === '' || $information === null) {
            return '';
        }

        $html = \core_availability\info::format_info($information, $course);
        $text = html_to_text($html, 0, false);
        $text = trim(clean_param($text, PARAM_TEXT));

        return preg_replace('/\s+/u', ' ', $text) ?? $text;
    }

    /**
     * Build a consistently typed result.
     *
     * @param bool $available Whether the user can access the chapter
     * @param string $information Safe plain-text information
     * @param bool $hasrestrictions Whether the chapter has an effective restriction
     * @return array{available: bool, information: string, hasRestrictions: bool}
     */
    private function result(bool $available, string $information, bool $hasrestrictions): array {
        return [
            'available' => $available,
            'information' => $information,
            'hasRestrictions' => $hasrestrictions,
        ];
    }
}
