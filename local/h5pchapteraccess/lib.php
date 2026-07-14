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
 * Library callbacks for local_h5pchapteraccess.
 *
 * Runtime logic belongs in autoloaded classes.
 *
 * @package    local_h5pchapteraccess
 * @copyright  2026 Luiz Gustavo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Add the chapter access page to compatible activity settings navigation.
 *
 * @param settings_navigation $settingsnav Settings navigation
 * @param context $context Current page context
 */
function local_h5pchapteraccess_extend_settings_navigation(
    settings_navigation $settingsnav,
    context $context
): void {
    \local_h5pchapteraccess\service\navigation_service::extend_settings_navigation($settingsnav, $context);
}
