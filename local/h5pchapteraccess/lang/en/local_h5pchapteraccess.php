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

/**
 * English language strings for local_h5pchapteraccess.
 *
 * @package    local_h5pchapteraccess
 * @copyright  2026 Luiz Gustavo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['h5pchapteraccess:manage'] = 'Manage H5P chapter access';
$string['h5pchapteraccess:viewlocked'] = 'View locked H5P chapters';
$string['error:accessdenied'] = 'You do not have access to H5P activity {$a}.';
$string['error:cmnotfound'] = 'Course module {$a} does not exist.';
$string['error:duplicatechapterid'] = 'The H5P book contains the duplicate chapter ID "{$a}".';
$string['error:h5pnotfound'] = 'No deployed core H5P content was found for activity {$a}.';
$string['error:incompatiblelibrary'] = 'The H5P main library "{$a}" is not supported.';
$string['error:instancenotfound'] = 'The h5pactivity instance for course module {$a} does not exist.';
$string['error:invalidchapters'] = 'The H5P book has an invalid chapters configuration.';
$string['error:invalidjson'] = 'The H5P content parameters are not valid JSON.';
$string['error:jsonunavailable'] = 'The file content/content.json could not be read from the H5P package.';
$string['error:packagenotfound'] = 'No H5P package was found for activity {$a}.';
$string['error:wrongmodule'] = 'The selected course module is "{$a}", not h5pactivity.';
$string['pluginname'] = 'H5P chapter access';
$string['privacy:metadata'] = 'The plugin stores access rules belonging to activities and chapters, not to individual users.';
