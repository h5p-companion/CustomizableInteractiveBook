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
 * Edit one H5P chapter using Moodle's standard availability condition editor.
 *
 * @package    local_h5pchapteraccess
 * @copyright  2026 Luiz Gustavo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$configpath = require(__DIR__ . '/bootstrap.php');
require_once($configpath);
unset($configpath);

use local_h5pchapteraccess\form\chapter_form;
use local_h5pchapteraccess\service\chapter_configuration_service;
use local_h5pchapteraccess\service\configuration_service;

$cmid = required_param('cmid', PARAM_INT);
$chapteruuid = required_param('chapter', PARAM_RAW_TRIMMED);
$configuration = new configuration_service();
$activitydata = $configuration->require_manageable_activity($cmid);
$manifest = $configuration->extract_manifest($cmid);
$chapterservice = new chapter_configuration_service();
$chapter = $chapterservice->require_editable_chapter($cmid, $chapteruuid, $manifest);

$pageurl = new moodle_url('/local/h5pchapteraccess/edit.php', [
    'cmid' => $cmid,
    'chapter' => $chapteruuid,
]);
$manageurl = new moodle_url('/local/h5pchapteraccess/manage.php', ['cmid' => $cmid]);
$PAGE->set_url($pageurl);
$PAGE->set_cm($activitydata->cm, $activitydata->course);
$PAGE->set_context($activitydata->context);
$PAGE->set_pagelayout('incourse');
$PAGE->set_title(get_string('editrestrictionstitle', 'local_h5pchapteraccess'));
$PAGE->set_heading(format_string($activitydata->course->fullname));
$PAGE->navbar->add(get_string('navigationtitle', 'local_h5pchapteraccess'), $manageurl);
$PAGE->navbar->add(get_string('editrestrictions', 'local_h5pchapteraccess'), $pageurl);

$form = new chapter_form($pageurl, [
    'cmid' => $cmid,
    'chapter' => $chapter,
    'course' => $activitydata->course,
    'cm' => $activitydata->cm,
]);
$form->set_data($chapterservice->get_form_data($cmid, $chapter));

if ($form->is_cancelled()) {
    redirect($manageurl);
}

if ($data = $form->get_data()) {
    require_sesskey();
    $chapterservice->save($cmid, $chapteruuid, $data);
    redirect(
        $manageurl,
        get_string('chapterconfigurationsaved', 'local_h5pchapteraccess'),
        null,
        \core\output\notification::NOTIFY_SUCCESS
    );
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('editchapterheading', 'local_h5pchapteraccess', (object) [
    'position' => (int) $chapter->positioncache + 1,
    'title' => format_string($chapter->titlecache, true, ['context' => $activitydata->context]),
]));
echo $OUTPUT->notification(
    get_string('restrictioneditorintro', 'local_h5pchapteraccess'),
    \core\output\notification::NOTIFY_INFO
);
$form->display();
echo $OUTPUT->footer();
