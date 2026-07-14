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

use core\hook\output\before_standard_head_html_generation;

/**
 * Hook callbacks for loading the H5P host bridge on supported embed pages.
 *
 * @package    local_h5pchapteraccess
 * @copyright  2026 Luiz Gustavo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class hook_callbacks {

    /**
     * Schedule the AMD listener before the H5P iframe markup is generated.
     *
     * The supported Interactive Book uses iframe embedding. Its immediate parent
     * is Moodle's /h5p/embed.php page, so no bridge is loaded on reports, editing
     * pages, management pages, or unrelated course modules.
     *
     * @param before_standard_head_html_generation $hook Head generation hook
     */
    public static function before_standard_head_html_generation(
        before_standard_head_html_generation $hook
    ): void {
        global $DB;

        $page = $hook->renderer->get_page();
        $cm = $page->cm;
        if (!$cm || $cm->modname !== 'h5pactivity') {
            return;
        }
        if ($page->url->get_path() !== '/h5p/embed.php') {
            return;
        }
        if (!$DB->record_exists('local_h5pca_book', ['cmid' => $cm->id, 'enabled' => 1])) {
            return;
        }

        $page->requires->js_call_amd('local_h5pchapteraccess/bridge', 'init', [(int) $cm->id]);
    }
}
