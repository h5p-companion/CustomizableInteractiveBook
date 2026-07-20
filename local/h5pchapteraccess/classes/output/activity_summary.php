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

namespace local_h5pchapteraccess\output;

use local_h5pchapteraccess\dto\manifest;

/**
 * Template context for the activity and manifest summary.
 *
 * @package    local_h5pchapteraccess
 * @copyright  2026 Luiz Gustavo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class activity_summary implements \renderable, \templatable {

    /** @var \stdClass Activity record. */
    private \stdClass $activity;

    /** @var \context_module Activity context. */
    private \context_module $context;

    /** @var manifest Current manifest. */
    private manifest $manifest;

    /** @var array Deployment diagnostics. */
    private array $diagnostics;

    /** @var \moodle_url Activity view URL. */
    private \moodle_url $activityurl;

    /**
     * Constructor.
     *
     * @param \stdClass $activity h5pactivity record
     * @param \context_module $context Activity context
     * @param manifest $manifest Current manifest
     * @param array $diagnostics Deployment diagnostics
     * @param \moodle_url $activityurl Activity view URL
     */
    public function __construct(
        \stdClass $activity,
        \context_module $context,
        manifest $manifest,
        array $diagnostics,
        \moodle_url $activityurl
    ) {
        $this->activity = $activity;
        $this->context = $context;
        $this->manifest = $manifest;
        $this->diagnostics = $diagnostics;
        $this->activityurl = $activityurl;
    }

    /**
     * Export safe scalar values for Mustache.
     *
     * @param \renderer_base $output Renderer
     * @return \stdClass
     */
    public function export_for_template(\renderer_base $output): \stdClass {
        $libraryversion = (string) $this->diagnostics['libraryversion'];
        if ($libraryversion === '') {
            $libraryvalue = get_string('libraryversionunknown', 'local_h5pchapteraccess');
        } else {
            $libraryvalue = get_string('libraryversionvalue', 'local_h5pchapteraccess', (object) [
                'installed' => $libraryversion,
                'minimum' => $this->diagnostics['minimumlibraryversion'],
            ]);
        }

        $checks = [
            $this->check(
                get_string('libraryruntime', 'local_h5pchapteraccess'),
                $libraryvalue,
                (bool) $this->diagnostics['librarycompatible'],
                true
            ),
            $this->check(
                get_string('bridgeasset', 'local_h5pchapteraccess'),
                get_string(
                    $this->diagnostics['bridgeassetavailable'] ? 'bridgeassetpresent' : 'bridgeassetmissing',
                    'local_h5pchapteraccess'
                ),
                (bool) $this->diagnostics['bridgeassetavailable'],
                true
            ),
            $this->check(
                get_string('integrationstatus', 'local_h5pchapteraccess'),
                get_string(
                    $this->diagnostics['integrationenabled'] ? 'statusenabled' : 'statusdisabled',
                    'local_h5pchapteraccess'
                ),
                (bool) $this->diagnostics['integrationenabled']
            ),
            $this->check(
                get_string('configuredrules', 'local_h5pchapteraccess'),
                get_string('configuredrulesvalue', 'local_h5pchapteraccess', (object) [
                    'restricted' => $this->diagnostics['restrictedcount'],
                    'total' => $this->diagnostics['activecount'],
                ]),
                $this->diagnostics['restrictedcount'] > 0
            ),
        ];

        $haserror = !$this->diagnostics['librarycompatible']
            || !$this->diagnostics['bridgeassetavailable'];
        $overviewclass = $haserror
            ? 'alert-danger'
            : ($this->diagnostics['ready'] ? 'alert-success' : 'alert-warning');

        return (object) [
            'activityname' => strip_tags(format_string(
                $this->activity->name,
                true,
                ['context' => $this->context]
            )),
            'cmid' => $this->manifest->get_cmid(),
            'contentid' => $this->manifest->get_content_id(),
            'contenthash' => $this->manifest->get_content_hash(),
            'manifesthash' => $this->manifest->get_manifest_hash(),
            'activecount' => $this->diagnostics['activecount'],
            'opencount' => $this->diagnostics['opencount'],
            'lockedcount' => $this->diagnostics['lockedcount'],
            'conditionalcount' => $this->diagnostics['conditionalcount'],
            'unstablecount' => $this->diagnostics['unstablecount'],
            'hasunstable' => $this->diagnostics['unstablecount'] > 0,
            'overviewclass' => $overviewclass,
            'overviewtitle' => get_string(
                $this->diagnostics['ready'] ? 'configurationreadytitle' : 'configurationattentiontitle',
                'local_h5pchapteraccess'
            ),
            'overviewmessage' => get_string(
                $this->diagnostics['ready'] ? 'configurationready' : 'configurationneedsattention',
                'local_h5pchapteraccess'
            ),
            'checks' => $checks,
            'activityurl' => $this->activityurl->out(false),
            'deploymentnotice' => get_string('deploymentnotice', 'local_h5pchapteraccess'),
            'studentviewnotice' => get_string('studentviewnotice', 'local_h5pchapteraccess'),
        ];
    }

    /**
     * Build one safe status row for the template.
     *
     * @param string $label Check label
     * @param string $value Human-readable value
     * @param bool $ok Whether the requirement is satisfied
     * @param bool $error Whether failure prevents communication entirely
     * @return array
     */
    private function check(string $label, string $value, bool $ok, bool $error = false): array {
        return [
            'label' => $label,
            'value' => $value,
            'statusclass' => $ok
                ? 'bg-success text-white'
                : ($error ? 'bg-danger text-white' : 'bg-warning text-dark'),
            'statuslabel' => get_string(
                $ok ? 'checkok' : ($error ? 'checkerror' : 'checkattention'),
                'local_h5pchapteraccess'
            ),
        ];
    }
}
