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

    /** @var array Deployment diagnostics. */
    private array $diagnostics;

    /** @var \moodle_url Activity view URL. */
    private \moodle_url $activityurl;

    /**
     * Constructor.
     *
     * @param \stdClass $activity h5pactivity record
     * @param \context_module $context Activity context
     * @param array $diagnostics Deployment diagnostics
     * @param \moodle_url $activityurl Activity view URL
     */
    public function __construct(
        \stdClass $activity,
        \context_module $context,
        array $diagnostics,
        \moodle_url $activityurl
    ) {
        $this->activity = $activity;
        $this->context = $context;
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
        $deploymentissues = [];
        if (!$this->diagnostics['librarycompatible']) {
            $deploymentissues[] = get_string('libraryincompatibleaction', 'local_h5pchapteraccess');
        }
        if (!$this->diagnostics['bridgeassetavailable']) {
            $deploymentissues[] = get_string('bridgeassetmissing', 'local_h5pchapteraccess');
        }
        $integrationenabled = (bool) $this->diagnostics['integrationenabled'];

        return (object) [
            'activityname' => strip_tags(format_string(
                $this->activity->name,
                true,
                ['context' => $this->context]
            )),
            'opencount' => $this->diagnostics['opencount'],
            'lockedcount' => $this->diagnostics['lockedcount'],
            'conditionalcount' => $this->diagnostics['conditionalcount'],
            'hasrules' => $this->diagnostics['restrictedcount'] > 0,
            'statusclass' => $integrationenabled ? 'is-active' : 'is-paused',
            'statustitle' => get_string(
                $integrationenabled ? 'accessactive' : 'accesspaused',
                'local_h5pchapteraccess'
            ),
            'statusmessage' => get_string(
                $integrationenabled ? 'accessactivemessage' : 'accesspausedmessage',
                'local_h5pchapteraccess'
            ),
            'hasdeploymentissues' => $deploymentissues !== [],
            'deploymentissues' => $deploymentissues,
            'activityurl' => $this->activityurl->out(false),
        ];
    }
}
