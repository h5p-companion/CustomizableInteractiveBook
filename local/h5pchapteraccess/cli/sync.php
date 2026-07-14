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
 * Synchronize one H5P activity chapter manifest.
 *
 * @package    local_h5pchapteraccess
 * @copyright  2026 Luiz Gustavo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);

$configpath = __DIR__ . '/../../../config.php';
if (!is_readable($configpath) && isset($argv[0])) {
    // Development junctions/symlinks can make __DIR__ resolve outside the Moodle tree.
    // In that case, derive the root from the path used to invoke this script, without resolving links.
    $invokedpath = $argv[0];
    if (!preg_match('~^(?:[a-zA-Z]:[\\\\/]|/)~', $invokedpath)) {
        $invokedpath = getcwd() . DIRECTORY_SEPARATOR . $invokedpath;
    }
    $invokedconfigpath = dirname($invokedpath, 4) . '/config.php';
    if (is_readable($invokedconfigpath)) {
        $configpath = $invokedconfigpath;
    }
}
if (!is_readable($configpath)) {
    fwrite(STDERR, "Moodle config.php could not be located.\n");
    exit(1);
}

require($configpath);
require_once($CFG->libdir . '/clilib.php');

use local_h5pchapteraccess\service\manifest_extractor;
use local_h5pchapteraccess\service\manifest_synchronizer;

$help = <<<HELP
Synchronize the chapter manifest for one H5P activity.

Usage:
  php local/h5pchapteraccess/cli/sync.php --cmid=123

Options:
  -c, --cmid=ID   Moodle course module ID for mod_h5pactivity.
  -h, --help      Show this help.

HELP;

[$options, $unrecognized] = cli_get_params(
    ['help' => false, 'cmid' => null],
    ['h' => 'help', 'c' => 'cmid']
);

if ($options['help']) {
    cli_writeln($help);
    exit(0);
}
if ($unrecognized) {
    cli_error('Unknown options: ' . implode(', ', $unrecognized));
}
if ($options['cmid'] === null || !ctype_digit((string) $options['cmid']) || (int) $options['cmid'] <= 0) {
    cli_error('A positive integer --cmid is required. Use --help for usage.');
}

// CLI execution is restricted at the operating-system level. It explicitly runs as the
// site administrator so the extractor and synchronizer still execute their normal Moodle
// context and capability checks instead of using skip-capability flags.
$admin = get_admin();
if (!$admin) {
    cli_error('The Moodle site administrator account could not be loaded.');
}
\core\session\manager::set_user($admin);

try {
    $manifest = (new manifest_extractor())->extract((int) $options['cmid']);
    $summary = (new manifest_synchronizer())->synchronize($manifest);
} catch (\Throwable $exception) {
    cli_error($exception->getMessage());
}

cli_heading('H5P chapter manifest synchronized');
cli_writeln('Course module: ' . $manifest->get_cmid());
cli_writeln('Library: ' . $manifest->get_machine_name());
cli_writeln('Content ID: ' . $manifest->get_content_id());
cli_writeln('Content hash: ' . $manifest->get_content_hash());
cli_writeln('Manifest hash: ' . $manifest->get_manifest_hash());
cli_writeln('Chapters: ' . count($manifest->get_chapters()));
cli_writeln('Created: ' . $summary['created']);
cli_writeln('Updated: ' . $summary['updated']);
cli_writeln('Reactivated: ' . $summary['reactivated']);
cli_writeln('Deactivated: ' . $summary['deactivated']);
if ($summary['unstableids']) {
    cli_writeln('Warning: unstable chapter IDs: ' . implode(', ', $summary['unstableids']));
}

exit(0);
