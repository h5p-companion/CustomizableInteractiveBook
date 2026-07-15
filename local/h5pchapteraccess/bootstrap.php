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
 * Locates Moodle's config.php before the Moodle bootstrap is available.
 *
 * The additional candidates support development installations where this
 * plugin directory is exposed through a Windows junction or a symbolic link.
 *
 * @package    local_h5pchapteraccess
 * @copyright  2026 Luiz Gustavo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

return (static function(): string {
    $candidates = [
        dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'config.php',
    ];

    // Resolve the Moodle URL root without following the plugin junction.
    $documentroot = $_SERVER['CONTEXT_DOCUMENT_ROOT'] ?? $_SERVER['DOCUMENT_ROOT'] ?? '';
    $scriptname = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    $pluginpath = '/local/h5pchapteraccess/';
    $pluginposition = strrpos($scriptname, $pluginpath);
    if ($documentroot !== '' && $pluginposition !== false) {
        $moodleurlpath = substr($scriptname, 0, $pluginposition);
        $candidates[] = rtrim($documentroot, '/\\')
            . str_replace('/', DIRECTORY_SEPARATOR, $moodleurlpath)
            . DIRECTORY_SEPARATOR . 'config.php';
    }

    // CLI commands are documented to run from the Moodle root.
    $candidates[] = getcwd() . DIRECTORY_SEPARATOR . 'config.php';
    if (PHP_SAPI === 'cli' && isset($_SERVER['argv'][0])) {
        $invokedpath = $_SERVER['argv'][0];
        if (!preg_match('~^(?:[a-zA-Z]:[\\\\/]|/)~', $invokedpath)) {
            $invokedpath = getcwd() . DIRECTORY_SEPARATOR . $invokedpath;
        }
        $candidates[] = dirname($invokedpath, 4) . DIRECTORY_SEPARATOR . 'config.php';
    }

    foreach (array_unique($candidates) as $candidate) {
        if (is_readable($candidate)) {
            return $candidate;
        }
    }

    throw new RuntimeException('Moodle config.php could not be located.');
})();
