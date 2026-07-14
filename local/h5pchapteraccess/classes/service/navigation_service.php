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

namespace local_h5pchapteraccess\service;

/**
 * Adds the lightweight activity settings navigation entry.
 *
 * @package    local_h5pchapteraccess
 * @copyright  2026 Luiz Gustavo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class navigation_service {

    /**
     * Add the management link when the current activity is supported and manageable.
     *
     * @param \settings_navigation $settingsnav Settings navigation
     * @param \context $context Current page context
     */
    public static function extend_settings_navigation(
        \settings_navigation $settingsnav,
        \context $context
    ): void {
        if (!$context instanceof \context_module) {
            return;
        }

        $page = $settingsnav->get_page();
        $cm = $page->cm;
        if (!$cm || $cm->modname !== 'h5pactivity') {
            return;
        }
        if (!has_capability('local/h5pchapteraccess:manage', $context)) {
            return;
        }

        try {
            $manifest = (new manifest_extractor())->extract((int) $cm->id);
            if ($manifest->get_machine_name() !== manifest_extractor::MACHINE_NAME) {
                return;
            }
        } catch (\Throwable $exception) {
            // Navigation must remain usable when an activity is invalid or has not been deployed by core_h5p yet.
            return;
        }

        $modulenode = $settingsnav->find('modulesettings', \navigation_node::TYPE_SETTING);
        if (!$modulenode) {
            return;
        }

        $url = new \moodle_url('/local/h5pchapteraccess/manage.php', ['cmid' => $cm->id]);
        $modulenode->add(
            get_string('navigationtitle', 'local_h5pchapteraccess'),
            $url,
            \navigation_node::TYPE_SETTING,
            null,
            'local_h5pchapteraccess_manage'
        );
    }
}
