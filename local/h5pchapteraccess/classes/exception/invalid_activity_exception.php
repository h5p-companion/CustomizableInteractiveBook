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

namespace local_h5pchapteraccess\exception;

/**
 * Exception for an invalid or inaccessible Moodle activity.
 *
 * @package    local_h5pchapteraccess
 * @copyright  2026 Luiz Gustavo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class invalid_activity_exception extends \moodle_exception {

    public const CM_NOT_FOUND = 'cmnotfound';
    public const WRONG_MODULE = 'wrongmodule';
    public const ACCESS_DENIED = 'accessdenied';
    public const INSTANCE_NOT_FOUND = 'instancenotfound';
    public const PACKAGE_NOT_FOUND = 'packagenotfound';
    public const H5P_NOT_FOUND = 'h5pnotfound';

    /**
     * Constructor.
     *
     * @param string $reason One of the class reason constants
     * @param mixed $a Optional data for the language string
     * @param string|null $debuginfo Optional debugging information
     */
    public function __construct(string $reason, $a = null, ?string $debuginfo = null) {
        parent::__construct('error:' . $reason, 'local_h5pchapteraccess', '', $a, $debuginfo);
    }
}
