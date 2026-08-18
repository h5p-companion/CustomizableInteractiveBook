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

    if ($oldversion < 2026071400) {
        // This release adds a manual configuration interface only; no schema change is required.
        upgrade_plugin_savepoint(true, 2026071400, 'local', 'h5pchapteraccess');
    }

    if ($oldversion < 2026071401) {
        // This release registers an AJAX service and output hook; no schema change is required.
        upgrade_plugin_savepoint(true, 2026071401, 'local', 'h5pchapteraccess');
    }

    if ($oldversion < 2026071402) {
        // This release evaluates stored chapter conditions; no schema change is required.
        upgrade_plugin_savepoint(true, 2026071402, 'local', 'h5pchapteraccess');
    }

    if ($oldversion < 2026071403) {
        // This release adds a standard Availability API editor; no schema change is required.
        upgrade_plugin_savepoint(true, 2026071403, 'local', 'h5pchapteraccess');
    }

    if ($oldversion < 2026071404) {
        // This release adds cache definitions, lifecycle observers and standard
        // activity backup/restore support; no database schema change is required.
        upgrade_plugin_savepoint(true, 2026071404, 'local', 'h5pchapteraccess');
    }

    if ($oldversion < 2026071405) {
        // Production review: query reuse and expanded tests; no schema change is required.
        upgrade_plugin_savepoint(true, 2026071405, 'local', 'h5pchapteraccess');
    }

    if ($oldversion < 2026071406) {
        // Stable documentation release; no schema change is required.
        upgrade_plugin_savepoint(true, 2026071406, 'local', 'h5pchapteraccess');
    }

    if ($oldversion < 2026071500) {
        // Activity navigation and management-form correction; no schema change is required.
        upgrade_plugin_savepoint(true, 2026071500, 'local', 'h5pchapteraccess');
    }

    if ($oldversion < 2026071501) {
        // Junction-safe web and CLI bootstrap; no schema change is required.
        upgrade_plugin_savepoint(true, 2026071501, 'local', 'h5pchapteraccess');
    }

    if ($oldversion < 2026071502) {
        // Safe lazy synchronization when the initial request has no sesskey; no schema change is required.
        upgrade_plugin_savepoint(true, 2026071502, 'local', 'h5pchapteraccess');
    }

    if ($oldversion < 2026071503) {
        // Normalize the module context ID before policy validation; no schema change is required.
        upgrade_plugin_savepoint(true, 2026071503, 'local', 'h5pchapteraccess');
    }

    if ($oldversion < 2026071600) {
        // Restore runtime content identity and editing-mode bypass behavior; no schema change is required.
        upgrade_plugin_savepoint(true, 2026071600, 'local', 'h5pchapteraccess');
    }

    if ($oldversion < 2026072000) {
        // Add deployment diagnostics and configuration usability improvements;
        // no database schema change is required.
        upgrade_plugin_savepoint(true, 2026072000, 'local', 'h5pchapteraccess');
    }

    if ($oldversion < 2026080400) {
        // Streamline the teacher interface and embed the standard availability
        // editor on the management page; no database schema change is required.
        upgrade_plugin_savepoint(true, 2026080400, 'local', 'h5pchapteraccess');
    }

    if ($oldversion < 2026080700) {
        // Refresh hook and language caches for the verified production package;
        // no database schema change is required.
        upgrade_plugin_savepoint(true, 2026080700, 'local', 'h5pchapteraccess');
    }

    return true;
}
