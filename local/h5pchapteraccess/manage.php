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
 * Manual H5P chapter access configuration page.
 *
 * @package    local_h5pchapteraccess
 * @copyright  2026 Luiz Gustavo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$configpath = require(__DIR__ . '/bootstrap.php');
require_once($configpath);
unset($configpath);

use local_h5pchapteraccess\form\manage_form;
use local_h5pchapteraccess\output\activity_summary;
use local_h5pchapteraccess\output\chapter_list;
use local_h5pchapteraccess\service\chapter_configuration_service;
use local_h5pchapteraccess\service\configuration_service;
use local_h5pchapteraccess\service\deployment_diagnostics;
use local_h5pchapteraccess\service\manifest_cache;

$cmid = required_param('cmid', PARAM_INT);
$action = optional_param('action', '', PARAM_ALPHA);
$selectedid = optional_param('chapter', '', PARAM_RAW_TRIMMED);
$service = new configuration_service();
$activitydata = $service->require_manageable_activity($cmid);

$pageurl = new moodle_url('/local/h5pchapteraccess/manage.php', ['cmid' => $cmid]);
$activityurl = new moodle_url('/mod/h5pactivity/view.php', ['id' => $cmid]);
$PAGE->set_url($pageurl);
$PAGE->set_cm($activitydata->cm, $activitydata->course);
$PAGE->set_context($activitydata->context);
$PAGE->set_pagelayout('incourse');
$PAGE->set_title(get_string('pagetitle', 'local_h5pchapteraccess'));
$PAGE->set_heading(format_string($activitydata->course->fullname));
$PAGE->navbar->add(get_string('navigationtitle', 'local_h5pchapteraccess'), $pageurl);

if ($action === 'sync') {
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
        throw new invalid_parameter_exception('Manifest synchronization requires POST.');
    }
    require_sesskey();
    // Explicit synchronization must re-read H5P even if the package hash did
    // not change (for example after an unusual external deployment workflow).
    (new manifest_cache())->delete($cmid);
}

// Extraction verifies that core_h5p identifies a supported Customizable Interactive Book.
$manifest = $service->extract_manifest($cmid);

if ($action === 'sync') {
    $service->synchronize_manifest($manifest);
    redirect(
        $pageurl,
        get_string('synchronizationsuccess', 'local_h5pchapteraccess'),
        null,
        \core\output\notification::NOTIFY_SUCCESS
    );
}

// An automatic synchronization is performed only when persistence is stale. The
// intermediate same-site redirect obtains a valid sesskey before any database write.
if ($service->needs_synchronization($manifest)) {
    $requestsesskey = optional_param('sesskey', '', PARAM_RAW);
    if ($requestsesskey === '' || !confirm_sesskey($requestsesskey)) {
        redirect(new moodle_url($pageurl, ['sesskey' => sesskey()]));
    }
    $service->synchronize_manifest($manifest);
    redirect($pageurl);
}

$configuration = $service->get_configuration($cmid);
$chapterservice = new chapter_configuration_service();
$activechapters = $chapterservice->prepare_for_manage(
    $configuration->activechapters,
    $activitydata->course,
    $activitydata->cm
);
$form = new manage_form($pageurl, [
    'cmid' => $cmid,
]);
$form->set_data($service->get_form_data($configuration));

$selectedchapter = null;
$chapterform = null;
if ($selectedid !== '') {
    $selectedchapter = $chapterservice->require_editable_chapter($cmid, $selectedid, $manifest);
    $chapterurl = new moodle_url($pageurl, ['chapter' => $selectedid]);
    $chapterurl->set_anchor('chapter-editor');
    $chapterform = new \local_h5pchapteraccess\form\chapter_form($chapterurl, [
        'cmid' => $cmid,
        'chapter' => $selectedchapter,
        'course' => $activitydata->course,
        'cm' => $activitydata->cm,
    ]);
    $chapterform->set_data($chapterservice->get_form_data($cmid, $selectedchapter));
}

if ($form->is_cancelled()) {
    redirect($activityurl);
}

if ($data = $form->get_data()) {
    require_sesskey();
    $service->save_activity_settings($cmid, $data);
    redirect(
        $pageurl,
        get_string('configurationsaved', 'local_h5pchapteraccess'),
        null,
        \core\output\notification::NOTIFY_SUCCESS
    );
}

if ($chapterform !== null && $chapterform->is_cancelled()) {
    redirect($pageurl);
}

if ($chapterform !== null && $chapterdata = $chapterform->get_data()) {
    require_sesskey();
    $chapterservice->save($cmid, $selectedid, $chapterdata);
    redirect(
        $chapterurl,
        get_string('chapterconfigurationsaved', 'local_h5pchapteraccess'),
        null,
        \core\output\notification::NOTIFY_SUCCESS
    );
}

echo $OUTPUT->header();

$summary = new activity_summary(
    $activitydata->activity,
    $activitydata->context,
    (new deployment_diagnostics())->inspect(
        $manifest,
        $configuration->book,
        $configuration->activechapters
    ),
    $activityurl
);
echo $OUTPUT->render_from_template(
    'local_h5pchapteraccess/activity_summary',
    $summary->export_for_template($OUTPUT)
);

echo html_writer::start_div('card mb-4');
echo html_writer::start_div('card-body');
echo $OUTPUT->heading(get_string('generalsettings', 'local_h5pchapteraccess'), 2, 'h4 mb-1');
echo html_writer::tag('p', s(get_string('generalsettingsintro', 'local_h5pchapteraccess')), [
    'class' => 'text-muted mb-3',
]);
$form->display();
echo html_writer::end_div();
echo html_writer::end_div();

$chapterlist = new chapter_list(
    $activechapters,
    $activitydata->context,
    $pageurl,
    $selectedid
);
echo $OUTPUT->render_from_template(
    'local_h5pchapteraccess/chapter_list',
    $chapterlist->export_for_template($OUTPUT)
);

if ($chapterform !== null && $selectedchapter !== null) {
    echo html_writer::start_tag('section', [
        'id' => 'chapter-editor',
        'class' => 'card local-h5pca-editor mt-4 mb-4',
        'aria-labelledby' => 'local-h5pca-editor-heading',
    ]);
    echo html_writer::start_div('card-body');
    echo html_writer::tag('p', get_string(
        'chapterpositionheading',
        'local_h5pchapteraccess',
        (int) $selectedchapter->positioncache + 1
    ), ['class' => 'text-muted mb-1']);
    echo html_writer::tag('h2', format_string(
        (string) $selectedchapter->titlecache,
        true,
        ['context' => $activitydata->context]
    ), [
        'id' => 'local-h5pca-editor-heading',
        'class' => 'h3 mb-2',
    ]);
    echo html_writer::tag('p', s(get_string('chaptereditorintro', 'local_h5pchapteraccess')), [
        'class' => 'text-muted mb-4',
    ]);
    $chapterform->display();
    echo html_writer::end_div();
    echo html_writer::end_tag('section');
}

$syncurl = new moodle_url($pageurl, ['action' => 'sync', 'sesskey' => sesskey()]);
echo html_writer::start_div('text-end mb-3');
echo $OUTPUT->single_button(
    $syncurl,
    get_string('synchronizeagain', 'local_h5pchapteraccess'),
    'post',
    ['class' => 'btn-sm']
);
echo html_writer::end_div();

echo $OUTPUT->footer();
