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
 * Exception for H5P content that cannot produce a valid book manifest.
 *
 * @package    local_h5pchapteraccess
 * @copyright  2026 Luiz Gustavo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class unsupported_content_exception extends \moodle_exception {

    public const INCOMPATIBLE_LIBRARY = 'incompatiblelibrary';
    public const JSON_UNAVAILABLE = 'jsonunavailable';
    public const INVALID_JSON = 'invalidjson';
    public const INVALID_CHAPTERS = 'invalidchapters';
    public const DUPLICATE_CHAPTER_ID = 'duplicatechapterid';

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
