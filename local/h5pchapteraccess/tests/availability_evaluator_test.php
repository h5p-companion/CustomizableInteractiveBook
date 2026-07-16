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

use availability_date\condition as date_condition;
use local_h5pchapteraccess\service\availability_evaluator;

/**
 * Tests for per-user chapter Availability API evaluation.
 *
 * @package    local_h5pchapteraccess
 * @category   test
 * @copyright  2026 Luiz Gustavo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class availability_evaluator_test extends \advanced_testcase {

    /** Fixed clock used by date conditions. */
    private const NOW = 1800000000;

    /**
     * Reset static date-condition state.
     */
    protected function tearDown(): void {
        date_condition::set_current_time_for_test();
        parent::tearDown();
    }

    /**
     * Open mode is available and has no restrictions.
     */
    public function test_open_is_available(): void {
        [$course, $cm, $student] = $this->create_fixture();

        $result = $this->evaluate($this->chapter('open'), $course, $cm, $student->id);

        $this->assertTrue($result['available']);
        $this->assertFalse($result['hasRestrictions']);
        $this->assertSame('', $result['information']);
    }

    /**
     * Locked mode is unavailable.
     */
    public function test_locked_is_unavailable(): void {
        [$course, $cm, $student] = $this->create_fixture();

        $result = $this->evaluate($this->chapter('locked'), $course, $cm, $student->id);

        $this->assertFalse($result['available']);
        $this->assertTrue($result['hasRestrictions']);
    }

    /**
     * Empty conditional JSON is intentionally open and reports a configuration warning.
     */
    public function test_conditional_without_json_is_available_with_warning(): void {
        [$course, $cm, $student] = $this->create_fixture();

        $result = $this->evaluate($this->chapter('conditional'), $course, $cm, $student->id);

        $this->assertTrue($result['available']);
        $this->assertFalse($result['hasRestrictions']);
        $this->assertSame(
            get_string('conditionalwithoutconditions', 'local_h5pchapteraccess'),
            $result['information']
        );
    }

    /**
     * A satisfied date condition allows the chapter.
     */
    public function test_satisfied_date_condition_is_available(): void {
        [$course, $cm, $student] = $this->create_fixture();
        date_condition::set_current_time_for_test(self::NOW);
        $chapter = $this->chapter('conditional', $this->date_json('>=', self::NOW - 60));
        $originaljson = $chapter->availabilityjson;

        $result = $this->evaluate($chapter, $course, $cm, $student->id);

        $this->assertTrue($result['available']);
        $this->assertTrue($result['hasRestrictions']);
        $this->assertSame('', $result['information']);
        $this->assertSame($originaljson, $chapter->availabilityjson);
    }

    /**
     * An unsatisfied date condition blocks the chapter and returns plain text.
     */
    public function test_unsatisfied_date_condition_is_unavailable_with_plain_information(): void {
        [$course, $cm, $student] = $this->create_fixture();
        date_condition::set_current_time_for_test(self::NOW);
        $chapter = $this->chapter('conditional', $this->date_json('>=', self::NOW + 3600));

        $result = $this->evaluate($chapter, $course, $cm, $student->id);

        $this->assertFalse($result['available']);
        $this->assertTrue($result['hasRestrictions']);
        $this->assertNotSame('', $result['information']);
        $this->assertSame(strip_tags($result['information']), $result['information']);
    }

    /**
     * Completion of another activity changes the conditional result.
     */
    public function test_activity_completion_condition(): void {
        [$course, $cm, $student] = $this->create_fixture();
        $page = $this->getDataGenerator()->create_module('page', [
            'course' => $course,
            'completion' => COMPLETION_TRACKING_MANUAL,
        ]);
        $pagecm = get_fast_modinfo($course, $student->id, true)->get_cm($page->cmid);
        $chapter = $this->chapter('conditional', $this->tree_json('&', [
            \availability_completion\condition::get_json($pagecm->id, COMPLETION_COMPLETE),
        ]));

        $this->assertFalse($this->evaluate($chapter, $course, $cm, $student->id)['available']);
        (new \completion_info($course))->update_state($pagecm, COMPLETION_COMPLETE, $student->id);
        $this->assertTrue($this->evaluate($chapter, $course, $cm, $student->id)['available']);
    }

    /**
     * A grade threshold is evaluated for the requested student.
     */
    public function test_grade_condition(): void {
        [$course, $cm, $student] = $this->create_fixture();
        $gradeitem = $this->getDataGenerator()->create_grade_item([
            'courseid' => $course->id,
            'itemname' => 'Required grade',
            'grademin' => 0,
            'grademax' => 100,
        ]);
        $chapter = $this->chapter('conditional', $this->tree_json('&', [
            \availability_grade\condition::get_json((int) $gradeitem->id, 70),
        ]));

        $this->assertFalse($this->evaluate($chapter, $course, $cm, $student->id)['available']);
        $this->getDataGenerator()->create_grade_grade([
            'itemid' => $gradeitem->id,
            'userid' => $student->id,
            'grade' => 75,
        ]);
        \cache::make('availability_grade', 'scores')->delete($student->id);
        $this->assertTrue($this->evaluate($chapter, $course, $cm, $student->id)['available']);
    }

    /**
     * AND requires every child condition to pass.
     */
    public function test_and_combination(): void {
        [$course, $cm, $student] = $this->create_fixture();
        date_condition::set_current_time_for_test(self::NOW);
        $chapter = $this->chapter('conditional', $this->tree_json('&', [
            date_condition::get_json('>=', self::NOW - 60),
            date_condition::get_json('>=', self::NOW + 60),
        ]));

        $this->assertFalse($this->evaluate($chapter, $course, $cm, $student->id)['available']);
    }

    /**
     * OR allows access when at least one child condition passes.
     */
    public function test_or_combination(): void {
        [$course, $cm, $student] = $this->create_fixture();
        date_condition::set_current_time_for_test(self::NOW);
        $chapter = $this->chapter('conditional', $this->tree_json('|', [
            date_condition::get_json('>=', self::NOW - 60),
            date_condition::get_json('>=', self::NOW + 60),
        ]));

        $this->assertTrue($this->evaluate($chapter, $course, $cm, $student->id)['available']);
    }

    /**
     * The plugin bypass capability wins over locked mode while editing is enabled.
     */
    public function test_viewlocked_capability_bypasses_restriction(): void {
        global $USER;

        [$course, $cm] = $this->create_fixture();
        $teacher = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($teacher->id, $course->id, 'editingteacher');
        $this->setUser($teacher);
        $USER->editing = 1;

        $result = $this->evaluate($this->chapter('locked'), $course, $cm, $teacher->id);

        $this->assertTrue($result['available']);
        $this->assertTrue($result['hasRestrictions']);
        $this->assertSame('', $result['information']);
    }

    /**
     * A teacher with editing disabled sees the same locked result as a student.
     */
    public function test_viewlocked_capability_without_editing_does_not_bypass(): void {
        global $USER;

        [$course, $cm] = $this->create_fixture();
        $teacher = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($teacher->id, $course->id, 'editingteacher');
        $this->setUser($teacher);
        $USER->editing = 0;

        $result = $this->evaluate($this->chapter('locked'), $course, $cm, $teacher->id);

        $this->assertFalse($result['available']);
        $this->assertTrue($result['hasRestrictions']);
    }

    /**
     * showrestriction hides Availability API details without changing access.
     */
    public function test_showrestriction_false_hides_condition_information(): void {
        [$course, $cm, $student] = $this->create_fixture();
        date_condition::set_current_time_for_test(self::NOW);
        $chapter = $this->chapter('conditional', $this->date_json('>=', self::NOW + 3600), false);

        $result = $this->evaluate($chapter, $course, $cm, $student->id);

        $this->assertFalse($result['available']);
        $this->assertSame('', $result['information']);
    }

    /**
     * Invalid JSON fails closed and exposes no parser details.
     */
    public function test_invalid_json_fails_closed(): void {
        [$course, $cm, $student] = $this->create_fixture();

        $result = $this->evaluate($this->chapter('conditional', '{invalid'), $course, $cm, $student->id);

        $this->assertFalse($result['available']);
        $this->assertTrue($result['hasRestrictions']);
        $this->assertSame(get_string('availabilityinvalid', 'local_h5pchapteraccess'), $result['information']);
        $this->assertDebuggingCalled(null, DEBUG_DEVELOPER);
    }

    /**
     * Availability conditions use the requested user rather than the current user.
     */
    public function test_explicit_user_is_used_instead_of_current_user(): void {
        [$course, $cm, $student] = $this->create_fixture();
        $currentuser = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($currentuser->id, $course->id, 'student');
        $group = $this->getDataGenerator()->create_group(['courseid' => $course->id]);
        groups_add_member($group, $currentuser);
        $this->setUser($currentuser);
        $chapter = $this->chapter('conditional', $this->group_json((int) $group->id));

        $result = $this->evaluate($chapter, $course, $cm, $student->id);

        $this->assertFalse($result['available']);
    }

    /**
     * Two students receive different decisions from the same group condition.
     */
    public function test_different_students_receive_different_results(): void {
        [$course, $cm, $studentoutside] = $this->create_fixture();
        $studentinside = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($studentinside->id, $course->id, 'student');
        $group = $this->getDataGenerator()->create_group(['courseid' => $course->id]);
        groups_add_member($group, $studentinside);
        $chapter = $this->chapter('conditional', $this->group_json((int) $group->id));

        $this->assertFalse($this->evaluate($chapter, $course, $cm, $studentoutside->id)['available']);
        $this->assertTrue($this->evaluate($chapter, $course, $cm, $studentinside->id)['available']);
    }

    /**
     * Create a course, H5P module and enrolled student.
     *
     * @return array Course, cm_info and user
     */
    private function create_fixture(): array {
        global $CFG;

        $this->resetAfterTest();
        $CFG->enableavailability = 1;
        $course = $this->getDataGenerator()->create_course(['enablecompletion' => 1]);
        $activity = $this->getDataGenerator()->create_module('h5pactivity', ['course' => $course]);
        $student = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($student->id, $course->id, 'student');
        $this->setUser($student);
        $cm = get_fast_modinfo($course, $student->id)->get_cm($activity->cmid);

        return [$course, $cm, $student];
    }

    /**
     * Build a stored chapter-shaped record.
     *
     * @param string $mode Access mode
     * @param string|null $availability Availability JSON
     * @param bool $showrestriction Whether to return condition details
     * @return \stdClass
     */
    private function chapter(string $mode, ?string $availability = null, bool $showrestriction = true): \stdClass {
        return (object) [
            'id' => 101,
            'bookid' => 202,
            'chapteruuid' => 'chapter-uuid',
            'titlecache' => 'Test chapter',
            'accessmode' => $mode,
            'availabilityjson' => $availability,
            'showrestriction' => (int) $showrestriction,
        ];
    }

    /**
     * Build a Moodle Availability API date tree.
     *
     * @param string $direction Date comparison direction
     * @param int $time Unix timestamp
     * @return string
     */
    private function date_json(string $direction, int $time): string {
        return $this->tree_json('&', [date_condition::get_json($direction, $time)]);
    }

    /**
     * Build a Moodle Availability API group tree.
     *
     * @param int $groupid Group ID
     * @return string
     */
    private function group_json(int $groupid): string {
        return $this->tree_json('&', [(object) ['type' => 'group', 'id' => $groupid]]);
    }

    /**
     * Build an Availability API tree from arbitrary child conditions.
     *
     * @param string $operator Root operator
     * @param \stdClass[] $conditions Child conditions
     * @return string
     */
    private function tree_json(string $operator, array $conditions): string {
        return json_encode((object) [
            'op' => $operator,
            'showc' => array_fill(0, count($conditions), true),
            'c' => $conditions,
        ], JSON_THROW_ON_ERROR);
    }

    /**
     * Evaluate using the service under test.
     *
     * @param \stdClass $chapter Chapter record
     * @param \stdClass $course Course record
     * @param \cm_info $cm Course module
     * @param int $userid User ID
     * @return array
     */
    private function evaluate(\stdClass $chapter, \stdClass $course, \cm_info $cm, int $userid): array {
        return (new availability_evaluator())->evaluate($chapter, $course, $cm, $userid);
    }
}
