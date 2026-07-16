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
use local_h5pchapteraccess\repository\book_repository;
use local_h5pchapteraccess\repository\chapter_repository;

/**
 * Builds the browser access contract from trusted server-side state.
 *
 * @package    local_h5pchapteraccess
 * @copyright  2026 Luiz Gustavo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class policy_builder {

    /** Browser contract version. */
    public const CONTRACT_VERSION = 1;

    /** @var \moodle_database Moodle database connection. */
    private \moodle_database $db;

    /** @var manifest_extractor Server-side H5P manifest extractor. */
    private manifest_extractor $extractor;

    /** @var manifest_synchronizer Manifest persistence service. */
    private manifest_synchronizer $synchronizer;

    /** @var configuration_service Configuration comparison service. */
    private configuration_service $configuration;

    /** @var book_repository Book persistence. */
    private book_repository $books;

    /** @var chapter_repository Chapter persistence. */
    private chapter_repository $chapters;

    /** @var availability_evaluator Per-user chapter availability evaluator. */
    private availability_evaluator $availability;

    /**
     * Constructor.
     *
     * @param \moodle_database|null $db Database connection, primarily for tests
     * @param manifest_extractor|null $extractor Manifest extractor
     * @param manifest_synchronizer|null $synchronizer Manifest synchronizer
     * @param availability_evaluator|null $availability Availability evaluator
     */
    public function __construct(
        ?\moodle_database $db = null,
        ?manifest_extractor $extractor = null,
        ?manifest_synchronizer $synchronizer = null,
        ?availability_evaluator $availability = null
    ) {
        global $DB;

        $this->db = $db ?? $DB;
        $this->extractor = $extractor ?? new manifest_extractor();
        $this->books = new book_repository($this->db);
        $this->chapters = new chapter_repository($this->db);
        $this->synchronizer = $synchronizer ?? new manifest_synchronizer(
            $this->books,
            $this->chapters,
            $this->db
        );
        $this->configuration = new configuration_service(
            $this->db,
            $this->extractor,
            $this->synchronizer
        );
        $this->availability = $availability ?? new availability_evaluator();
    }

    /**
     * Authorize the activity, extract its manifest and build the current-user policy.
     *
     * @param int $cmid Course module ID
     * @param int $requestedcontentid H5P content ID received from the iframe, or zero when omitted
     * @return array Browser contract fields
     */
    public function build_for_activity(int $cmid, int $requestedcontentid = 0): array {
        $manifest = $this->extractor->extract($cmid);
        if ($requestedcontentid > 0 && $requestedcontentid !== $manifest->get_content_id()) {
            throw new \moodle_exception('error:contentmismatch', 'local_h5pchapteraccess');
        }

        $context = \context_module::instance($cmid);
        return $this->build_from_manifest($manifest, $context);
    }

    /**
     * Build a policy from a manifest already extracted by the server.
     *
     * This method exists separately to keep policy calculation deterministic and
     * testable. The context must match the manifest and the current user must be
     * allowed to view the H5P activity.
     *
     * @param manifest $manifest Trusted server-side manifest
     * @param \context_module $context Activity context
     * @return array Browser contract fields
     */
    public function build_from_manifest(manifest $manifest, \context_module $context): array {
        global $USER;

        // Context records are commonly hydrated from the database with numeric
        // fields represented as strings, while the manifest DTO is strictly typed.
        if ((int) $context->instanceid !== $manifest->get_cmid()) {
            throw new \coding_exception('The policy context does not match the manifest course module.');
        }
        require_capability('mod/h5pactivity:view', $context);

        $userid = (int) $USER->id;
        $teacherbypass = $this->availability->is_bypass_active($context, $userid);
        $book = $this->books->get_by_cmid($manifest->get_cmid());

        // No activity configuration means that the integration has not been enabled.
        if ($book === null) {
            return $this->allow_all($manifest, false, $teacherbypass);
        }

        $storedchapters = $this->chapters->get_by_book_id((int) $book->id);
        if ($this->configuration->needs_synchronization($manifest, $book, $storedchapters)) {
            $this->synchronizer->synchronize_for_policy($manifest);
            $book = $this->books->get_by_cmid($manifest->get_cmid());
            $storedchapters = $book ? $this->chapters->get_by_book_id((int) $book->id) : [];
        }

        if ($book === null || !(bool) $book->enabled) {
            return $this->allow_all($manifest, false, $teacherbypass);
        }

        // A user with the explicit capability and active Moodle editing mode
        // does not need per-chapter Availability API evaluation. Returning here
        // avoids loading modinfo and evaluating conditions needlessly.
        if ($teacherbypass) {
            return $this->allow_all($manifest, true, true);
        }

        $course = get_course($context->get_course_context()->instanceid);
        $cm = get_fast_modinfo($course, $userid)->get_cm($manifest->get_cmid());

        $policychapters = [];
        foreach ($manifest->get_chapters() as $manifestchapter) {
            $chapterid = $manifestchapter->get_id();
            $record = $storedchapters[$chapterid] ?? null;
            $available = true;
            $message = '';

            if ($record !== null && (bool) $record->active) {
                $evaluation = $this->availability->evaluate($record, $course, $cm, $userid);
                $available = $evaluation['available'];
                if (!$available) {
                    $message = $this->resolve_message($book, $record, $evaluation['information']);
                }
            }

            $policychapters[$chapterid] = [
                'available' => $available,
                'message' => $message,
            ];
        }

        return [
            'contractVersion' => self::CONTRACT_VERSION,
            'contentId' => $manifest->get_content_id(),
            'required' => true,
            'teacherBypass' => $teacherbypass,
            'chapters' => $policychapters,
        ];
    }

    /**
     * Build an explicit allow-all response from the trusted manifest.
     *
     * @param manifest $manifest Trusted manifest
     * @param bool $required Whether the host integration is required
     * @param bool $teacherbypass Whether the current user has an active editing bypass
     * @return array Browser contract fields
     */
    private function allow_all(manifest $manifest, bool $required, bool $teacherbypass): array {
        $chapters = [];
        foreach ($manifest->get_chapters() as $chapter) {
            $chapters[$chapter->get_id()] = [
                'available' => true,
                'message' => '',
            ];
        }

        return [
            'contractVersion' => self::CONTRACT_VERSION,
            'contentId' => $manifest->get_content_id(),
            'required' => $required,
            'teacherBypass' => $teacherbypass,
            'chapters' => $chapters,
        ];
    }

    /**
     * Resolve and clean the effective plain-text lock message.
     *
     * @param \stdClass $book Book configuration
     * @param \stdClass $chapter Chapter configuration
     * @param string $availabilityinformation Plain-text Availability API information
     * @return string
     */
    private function resolve_message(
        \stdClass $book,
        \stdClass $chapter,
        string $availabilityinformation = ''
    ): string {
        $message = trim((string) ($chapter->lockedmessage ?? ''));
        if ($message === '') {
            $message = trim($availabilityinformation);
        }
        if ($message === '') {
            $message = trim((string) ($book->defaultmessage ?? ''));
        }
        if ($message === '') {
            $message = get_string('defaultlockedmessage', 'local_h5pchapteraccess');
        }

        return trim(clean_param($message, PARAM_TEXT));
    }
}
