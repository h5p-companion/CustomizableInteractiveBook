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
use local_h5pchapteraccess\service\manifest_extractor;
use local_h5pchapteraccess\service\manifest_synchronizer;
use local_h5pchapteraccess\service\policy_builder;

/**
 * Tests for current-user policy calculation.
 *
 * @package    local_h5pchapteraccess
 * @category   test
 * @copyright  2026 Luiz Gustavo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class policy_builder_test extends \advanced_testcase {

    /**
     * An activity without plugin configuration is explicitly allow-all and not required.
     */
    public function test_activity_without_configuration_is_not_required(): void {
        $this->resetAfterTest();
        [$course, $cmid, $context] = $this->create_activity();
        $manifest = $this->manifest($cmid);
        $this->set_student($course);

        $policy = (new policy_builder())->build_from_manifest($manifest, $context);

        $this->assertFalse($policy['required']);
        $this->assertFalse($policy['teacherBypass']);
        $this->assertTrue($policy['chapters']['uuid-a']['available']);
        $this->assertTrue($policy['chapters']['uuid-b']['available']);
    }

    /**
     * Open chapters remain available to a student.
     */
    public function test_all_open_chapters_are_available_to_student(): void {
        $this->resetAfterTest();
        [$course, $cmid, $context] = $this->create_activity();
        $manifest = $this->manifest($cmid);
        $this->synchronize_as_admin($manifest);
        $this->set_student($course);

        $policy = (new policy_builder())->build_from_manifest($manifest, $context);

        $this->assertTrue($policy['required']);
        $this->assertFalse($policy['teacherBypass']);
        $this->assertTrue($policy['chapters']['uuid-a']['available']);
        $this->assertTrue($policy['chapters']['uuid-b']['available']);
    }

    /**
     * A specific message wins over visible Availability API information.
     */
    public function test_specific_plain_text_message_precedes_condition_information(): void {
        global $DB;
        $this->resetAfterTest();
        [$course, $cmid, $context] = $this->create_activity();
        $manifest = $this->manifest($cmid);
        $this->synchronize_as_admin($manifest);

        $book = $DB->get_record('local_h5pca_book', ['cmid' => $cmid], '*', MUST_EXIST);
        $chapter = $DB->get_record(
            'local_h5pca_chapter',
            ['bookid' => $book->id, 'chapteruuid' => 'uuid-b'],
            '*',
            MUST_EXIST
        );
        $chapter->accessmode = 'conditional';
        $chapter->lockedmessage = '<b>Specific message</b>';
        $chapter->availabilityjson = $this->future_date_json();
        $DB->update_record('local_h5pca_chapter', $chapter);
        $this->set_student($course);

        $policy = (new policy_builder())->build_from_manifest($manifest, $context);

        $this->assertTrue($policy['chapters']['uuid-a']['available']);
        $this->assertFalse($policy['chapters']['uuid-b']['available']);
        $this->assertSame('Specific message', $policy['chapters']['uuid-b']['message']);
        $this->assertArrayNotHasKey('availabilityjson', $policy['chapters']['uuid-b']);
    }

    /**
     * An empty chapter message falls back to the cleaned activity default.
     */
    public function test_locked_chapter_uses_default_plain_text_message(): void {
        global $DB;
        $this->resetAfterTest();
        [$course, $cmid, $context] = $this->create_activity();
        $manifest = $this->manifest($cmid);
        $this->synchronize_as_admin($manifest);

        $book = $DB->get_record('local_h5pca_book', ['cmid' => $cmid], '*', MUST_EXIST);
        $book->defaultmessage = '<em>Activity default</em>';
        $DB->update_record('local_h5pca_book', $book);
        $chapter = $DB->get_record(
            'local_h5pca_chapter',
            ['bookid' => $book->id, 'chapteruuid' => 'uuid-b'],
            '*',
            MUST_EXIST
        );
        $chapter->accessmode = 'locked';
        $chapter->lockedmessage = '';
        $DB->update_record('local_h5pca_chapter', $chapter);
        $this->set_student($course);

        $policy = (new policy_builder())->build_from_manifest($manifest, $context);

        $this->assertSame('Activity default', $policy['chapters']['uuid-b']['message']);
    }

    /**
     * Conditional mode without a tree remains safely available.
     */
    public function test_conditional_mode_without_json_is_available(): void {
        global $DB;
        $this->resetAfterTest();
        [$course, $cmid, $context] = $this->create_activity();
        $manifest = $this->manifest($cmid);
        $this->synchronize_as_admin($manifest);

        $bookid = $DB->get_field('local_h5pca_book', 'id', ['cmid' => $cmid], MUST_EXIST);
        $chapter = $DB->get_record(
            'local_h5pca_chapter',
            ['bookid' => $bookid, 'chapteruuid' => 'uuid-b'],
            '*',
            MUST_EXIST
        );
        $chapter->accessmode = 'conditional';
        $DB->update_record('local_h5pca_chapter', $chapter);
        $this->set_student($course);

        $policy = (new policy_builder())->build_from_manifest($manifest, $context);

        $this->assertTrue($policy['chapters']['uuid-b']['available']);
        $this->assertSame('', $policy['chapters']['uuid-b']['message']);
    }

    /**
     * Visible Availability API information takes precedence over the activity default.
     */
    public function test_conditional_information_precedes_default_message(): void {
        global $DB;
        $this->resetAfterTest();
        [$course, $cmid, $context] = $this->create_activity();
        $manifest = $this->manifest($cmid);
        $this->synchronize_as_admin($manifest);

        $book = $DB->get_record('local_h5pca_book', ['cmid' => $cmid], '*', MUST_EXIST);
        $book->defaultmessage = 'Activity fallback';
        $DB->update_record('local_h5pca_book', $book);
        $chapter = $DB->get_record(
            'local_h5pca_chapter',
            ['bookid' => $book->id, 'chapteruuid' => 'uuid-b'],
            '*',
            MUST_EXIST
        );
        $chapter->accessmode = 'conditional';
        $chapter->availabilityjson = $this->future_date_json();
        $chapter->showrestriction = 1;
        $DB->update_record('local_h5pca_chapter', $chapter);
        $this->set_student($course);

        $policy = (new policy_builder())->build_from_manifest($manifest, $context);

        $message = $policy['chapters']['uuid-b']['message'];
        $this->assertFalse($policy['chapters']['uuid-b']['available']);
        $this->assertNotSame('', $message);
        $this->assertNotSame('Activity fallback', $message);
        $this->assertSame(strip_tags($message), $message);
    }

    /**
     * Hidden condition details fall through to the activity default message.
     */
    public function test_hidden_conditional_information_uses_default_message(): void {
        global $DB;
        $this->resetAfterTest();
        [$course, $cmid, $context] = $this->create_activity();
        $manifest = $this->manifest($cmid);
        $this->synchronize_as_admin($manifest);

        $book = $DB->get_record('local_h5pca_book', ['cmid' => $cmid], '*', MUST_EXIST);
        $book->defaultmessage = 'Activity fallback';
        $DB->update_record('local_h5pca_book', $book);
        $chapter = $DB->get_record(
            'local_h5pca_chapter',
            ['bookid' => $book->id, 'chapteruuid' => 'uuid-b'],
            '*',
            MUST_EXIST
        );
        $chapter->accessmode = 'conditional';
        $chapter->availabilityjson = $this->future_date_json();
        $chapter->showrestriction = 0;
        $DB->update_record('local_h5pca_chapter', $chapter);
        $this->set_student($course);

        $policy = (new policy_builder())->build_from_manifest($manifest, $context);

        $this->assertFalse($policy['chapters']['uuid-b']['available']);
        $this->assertSame('Activity fallback', $policy['chapters']['uuid-b']['message']);
    }

    /**
     * The plugin string is the final fallback for an unavailable chapter.
     */
    public function test_locked_chapter_uses_plugin_default_message(): void {
        global $DB;
        $this->resetAfterTest();
        [$course, $cmid, $context] = $this->create_activity();
        $manifest = $this->manifest($cmid);
        $this->synchronize_as_admin($manifest);

        $book = $DB->get_record('local_h5pca_book', ['cmid' => $cmid], '*', MUST_EXIST);
        $book->defaultmessage = '';
        $DB->update_record('local_h5pca_book', $book);
        $chapter = $DB->get_record(
            'local_h5pca_chapter',
            ['bookid' => $book->id, 'chapteruuid' => 'uuid-b'],
            '*',
            MUST_EXIST
        );
        $chapter->accessmode = 'locked';
        $chapter->lockedmessage = '';
        $DB->update_record('local_h5pca_chapter', $chapter);
        $this->set_student($course);

        $policy = (new policy_builder())->build_from_manifest($manifest, $context);

        $this->assertSame(
            get_string('defaultlockedmessage', 'local_h5pchapteraccess'),
            $policy['chapters']['uuid-b']['message']
        );
    }

    /**
     * The viewlocked capability produces a teacher bypass without changing stored rules.
     */
    public function test_teacher_receives_bypass_and_all_chapters_available(): void {
        global $DB;
        $this->resetAfterTest();
        [$course, $cmid, $context] = $this->create_activity();
        $manifest = $this->manifest($cmid);
        $this->synchronize_as_admin($manifest);

        $bookid = $DB->get_field('local_h5pca_book', 'id', ['cmid' => $cmid], MUST_EXIST);
        $chapter = $DB->get_record(
            'local_h5pca_chapter',
            ['bookid' => $bookid, 'chapteruuid' => 'uuid-b'],
            '*',
            MUST_EXIST
        );
        $chapter->accessmode = 'locked';
        $DB->update_record('local_h5pca_chapter', $chapter);
        $teacher = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($teacher->id, $course->id, 'editingteacher');
        $this->setUser($teacher);

        $policy = (new policy_builder())->build_from_manifest($manifest, $context);

        $this->assertTrue($policy['teacherBypass']);
        $this->assertTrue($policy['chapters']['uuid-b']['available']);
        $this->assertSame('locked', $DB->get_field('local_h5pca_chapter', 'accessmode', ['id' => $chapter->id]));
    }

    /**
     * Disabling the integration preserves records but makes the contract optional and open.
     */
    public function test_disabled_integration_is_not_required_and_allows_all(): void {
        global $DB;
        $this->resetAfterTest();
        [$course, $cmid, $context] = $this->create_activity();
        $manifest = $this->manifest($cmid);
        $this->synchronize_as_admin($manifest);

        $book = $DB->get_record('local_h5pca_book', ['cmid' => $cmid], '*', MUST_EXIST);
        $book->enabled = 0;
        $DB->update_record('local_h5pca_book', $book);
        $this->set_student($course);

        $policy = (new policy_builder())->build_from_manifest($manifest, $context);

        $this->assertFalse($policy['required']);
        $this->assertTrue($policy['chapters']['uuid-a']['available']);
        $this->assertTrue($policy['chapters']['uuid-b']['available']);
    }

    /**
     * Create an empty H5P activity.
     *
     * @return array Course, cmid and module context
     */
    private function create_activity(): array {
        $course = $this->getDataGenerator()->create_course();
        $activity = $this->getDataGenerator()->create_module('h5pactivity', ['course' => $course]);
        $cmid = (int) $activity->cmid;
        return [$course, $cmid, \context_module::instance($cmid)];
    }

    /**
     * Enrol and select a student.
     *
     * @param \stdClass $course Course record
     */
    private function set_student(\stdClass $course): void {
        $student = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($student->id, $course->id, 'student');
        $this->setUser($student);
    }

    /**
     * Synchronize test records with management authorization.
     *
     * @param manifest $manifest Synthetic manifest
     */
    private function synchronize_as_admin(manifest $manifest): void {
        $this->setAdminUser();
        (new manifest_synchronizer())->synchronize($manifest);
    }

    /**
     * Build a synthetic two-chapter manifest.
     *
     * @param int $cmid Course module ID
     * @return manifest
     */
    private function manifest(int $cmid): manifest {
        return new manifest(
            $cmid,
            manifest_extractor::MACHINE_NAME,
            101,
            'content-hash',
            [
                new chapter('uuid-a', 'First', 0, true),
                new chapter('uuid-b', 'Second', 1, true),
            ]
        );
    }

    /**
     * Return an unsatisfied, visible date condition.
     *
     * @return string
     */
    private function future_date_json(): string {
        return json_encode((object) [
            'op' => '&',
            'showc' => [true],
            'c' => [\availability_date\condition::get_json('>=', time() + DAYSECS)],
        ], JSON_THROW_ON_ERROR);
    }
}
