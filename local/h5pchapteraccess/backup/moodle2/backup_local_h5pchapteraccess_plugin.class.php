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
 * Activity-level backup support for local_h5pchapteraccess.
 *
 * @package    local_h5pchapteraccess
 * @copyright  2026 Luiz Gustavo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Adds chapter access configuration to Moodle's standard module backup.
 */
class backup_local_h5pchapteraccess_plugin extends backup_local_plugin {

    /**
     * Define the structure connected to one course module.
     *
     * Non-H5P modules produce an empty structure because no book record can
     * match their course module ID.
     *
     * @return backup_plugin_element
     */
    protected function define_module_plugin_structure(): backup_plugin_element {
        $plugin = $this->get_plugin_element();
        $wrapper = new backup_nested_element($this->get_recommended_name());
        $book = new backup_nested_element('book', ['id'], [
            'enabled',
            'defaultmessage',
            'timecreated',
            'timemodified',
        ]);
        $chapter = new backup_nested_element('chapter', ['id'], [
            'chapteruuid',
            'titlecache',
            'positioncache',
            'stableid',
            'accessmode',
            'availabilityjson',
            'lockedmessage',
            'showrestriction',
            'active',
            'timecreated',
            'timemodified',
        ]);

        $plugin->add_child($wrapper);
        $wrapper->add_child($book);
        $book->add_child($chapter);

        $book->set_source_table('local_h5pca_book', ['cmid' => backup::VAR_MODID]);
        $chapter->set_source_table('local_h5pca_chapter', ['bookid' => backup::VAR_PARENTID]);

        return $plugin;
    }
}
