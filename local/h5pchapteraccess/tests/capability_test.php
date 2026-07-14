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

/**
 * Tests default capability assignments in an activity context.
 *
 * @package    local_h5pchapteraccess
 * @category   test
 * @copyright  2026 Luiz Gustavo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class capability_test extends \advanced_testcase {

    /** Editing teachers manage and bypass while students do neither. */
    public function test_default_role_capabilities(): void {
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $activity = $this->getDataGenerator()->create_module('h5pactivity', ['course' => $course]);
        $context = \context_module::instance((int) $activity->cmid);
        $teacher = $this->getDataGenerator()->create_user();
        $student = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($teacher->id, $course->id, 'editingteacher');
        $this->getDataGenerator()->enrol_user($student->id, $course->id, 'student');

        $this->assertTrue(has_capability('local/h5pchapteraccess:manage', $context, $teacher->id));
        $this->assertTrue(has_capability('local/h5pchapteraccess:viewlocked', $context, $teacher->id));
        $this->assertFalse(has_capability('local/h5pchapteraccess:manage', $context, $student->id));
        $this->assertFalse(has_capability('local/h5pchapteraccess:viewlocked', $context, $student->id));
    }
}
