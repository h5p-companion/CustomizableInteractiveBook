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
 * Upgrade steps for local_h5pchapteraccess.
 *
 * @package    local_h5pchapteraccess
 * @copyright  2026 Luiz Gustavo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Upgrade local_h5pchapteraccess.
 *
 * @param int $oldversion Installed plugin version.
 * @return bool
 */
function xmldb_local_h5pchapteraccess_upgrade(int $oldversion): bool {
    if ($oldversion < 2026071301) {
        // This release adds autoloaded services only; no schema change is required.
        upgrade_plugin_savepoint(true, 2026071301, 'local', 'h5pchapteraccess');
    }

    return true;
}
