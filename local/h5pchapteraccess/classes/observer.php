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

use local_h5pchapteraccess\service\cleanup_service;
use local_h5pchapteraccess\service\manifest_cache;

/**
 * Handles lifecycle events relevant to H5P chapter configuration.
 *
 * @package    local_h5pchapteraccess
 * @copyright  2026 Luiz Gustavo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class observer {

    /**
     * Invalidate structural data after an H5P activity update.
     *
     * Synchronization remains lazy so this event does not depend on package
     * deployment ordering inside mod_h5pactivity.
     *
     * @param \core\event\course_module_updated $event Moodle event
     */
    public static function course_module_updated(\core\event\course_module_updated $event): void {
        if (($event->other['modulename'] ?? '') !== 'h5pactivity') {
            return;
        }
        (new manifest_cache())->delete((int) $event->objectid);
    }

    /**
     * Remove plugin data after permanent H5P activity deletion.
     *
     * @param \core\event\course_module_deleted $event Moodle event
     */
    public static function course_module_deleted(\core\event\course_module_deleted $event): void {
        if (($event->other['modulename'] ?? '') !== 'h5pactivity') {
            return;
        }

        $cmid = (int) $event->objectid;
        (new manifest_cache())->delete($cmid);
        (new cleanup_service())->delete_by_cmid($cmid);
    }

    /**
     * Perform a defensive orphan cleanup after a complete course deletion.
     *
     * @param \core\event\course_deleted $event Moodle event
     */
    public static function course_deleted(\core\event\course_deleted $event): void {
        (new cleanup_service())->delete_orphans();
    }
}
