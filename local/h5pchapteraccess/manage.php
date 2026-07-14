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

require(__DIR__ . '/../../config.php');

use local_h5pchapteraccess\form\manage_form;
use local_h5pchapteraccess\output\activity_summary;
use local_h5pchapteraccess\output\inactive_chapters;
use local_h5pchapteraccess\service\chapter_configuration_service;
use local_h5pchapteraccess\service\configuration_service;
use local_h5pchapteraccess\service\manifest_cache;

$cmid = required_param('cmid', PARAM_INT);
$action = optional_param('action', '', PARAM_ALPHA);
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
    if (!confirm_sesskey()) {
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
    'chapters' => $activechapters,
]);
$form->set_data($service->get_form_data($configuration));

if ($form->is_cancelled()) {
    redirect($activityurl);
}

if ($data = $form->get_data()) {
    require_sesskey();
    $service->save($cmid, $data);
    redirect(
        $pageurl,
        get_string('configurationsaved', 'local_h5pchapteraccess'),
        null,
        \core\output\notification::NOTIFY_SUCCESS
    );
}

echo $OUTPUT->header();

$summary = new activity_summary(
    $activitydata->activity,
    $activitydata->context,
    $manifest,
    $configuration->book
);
echo $OUTPUT->render_from_template(
    'local_h5pchapteraccess/activity_summary',
    $summary->export_for_template($OUTPUT)
);

$syncurl = new moodle_url($pageurl, ['action' => 'sync', 'sesskey' => sesskey()]);
echo $OUTPUT->single_button(
    $syncurl,
    get_string('synchronizeagain', 'local_h5pchapteraccess'),
    'post',
    ['class' => 'mb-3']
);

$form->display();

$inactive = new inactive_chapters($configuration->inactivechapters);
echo $OUTPUT->render_from_template(
    'local_h5pchapteraccess/inactive_chapters',
    $inactive->export_for_template($OUTPUT)
);

echo $OUTPUT->footer();
