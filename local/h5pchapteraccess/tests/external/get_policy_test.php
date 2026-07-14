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

namespace local_h5pchapteraccess\external;

use local_h5pchapteraccess\exception\invalid_activity_exception;

/**
 * Authorization tests for the policy external function.
 *
 * @package    local_h5pchapteraccess
 * @category   test
 * @copyright  2026 Luiz Gustavo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class get_policy_test extends \advanced_testcase {

    /**
     * The AJAX function cannot be called anonymously.
     */
    public function test_execute_requires_login(): void {
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $activity = $this->getDataGenerator()->create_module('h5pactivity', ['course' => $course]);
        $this->setUser(0);

        $this->expectException(\moodle_exception::class);
        get_policy::execute((int) $activity->cmid);
    }

    /**
     * A student cannot request policy for an activity in another inaccessible course.
     */
    public function test_execute_rejects_cmid_from_another_course(): void {
        $this->resetAfterTest();
        $allowedcourse = $this->getDataGenerator()->create_course();
        $othercourse = $this->getDataGenerator()->create_course();
        $activity = $this->getDataGenerator()->create_module('h5pactivity', ['course' => $othercourse]);
        $student = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($student->id, $allowedcourse->id, 'student');
        $this->setUser($student);

        $this->expectException(\moodle_exception::class);
        get_policy::execute((int) $activity->cmid);
    }

    /**
     * Another module type is rejected before any H5P processing.
     */
    public function test_execute_rejects_non_h5p_activity(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $activity = $this->getDataGenerator()->create_module('page', ['course' => $course]);

        $this->expectException(invalid_activity_exception::class);
        get_policy::execute((int) $activity->cmid);
    }
}
