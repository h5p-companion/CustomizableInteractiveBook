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

namespace local_h5pchapteraccess\external;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;
use local_h5pchapteraccess\exception\invalid_activity_exception;
use local_h5pchapteraccess\service\policy_builder;

/**
 * AJAX endpoint returning the current user's H5P chapter access policy.
 *
 * @package    local_h5pchapteraccess
 * @copyright  2026 Luiz Gustavo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class get_policy extends external_api {

    /**
     * Describe the AJAX parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'cmid' => new external_value(PARAM_INT, 'H5P activity course module ID'),
            'contentid' => new external_value(
                PARAM_INT,
                'H5P content ID announced by the iframe; zero means use the server value',
                VALUE_DEFAULT,
                0
            ),
        ]);
    }

    /**
     * Build and serialize a trusted policy for the authenticated user.
     *
     * @param int $cmid H5P activity course module ID
     * @param int $contentid H5P content ID announced by the iframe
     * @return array Serialized policy
     */
    public static function execute(int $cmid, int $contentid = 0): array {
        ['cmid' => $cmid, 'contentid' => $contentid] = self::validate_parameters(
            self::execute_parameters(),
            ['cmid' => $cmid, 'contentid' => $contentid]
        );

        try {
            [$course, $cm] = get_course_and_cm_from_cmid($cmid);
        } catch (\moodle_exception $exception) {
            throw new invalid_activity_exception(
                invalid_activity_exception::CM_NOT_FOUND,
                $cmid,
                $exception->getMessage()
            );
        }

        if ($cm->modname !== 'h5pactivity') {
            throw new invalid_activity_exception(invalid_activity_exception::WRONG_MODULE, $cm->modname);
        }

        require_login($course, true, $cm);
        $context = \context_module::instance($cmid);
        self::validate_context($context);
        require_capability('mod/h5pactivity:view', $context);

        $policy = (new policy_builder())->build_for_activity($cmid, $contentid);
        return [
            'policy' => json_encode(
                $policy,
                JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
            ),
        ];
    }

    /**
     * Describe the serialized browser contract.
     *
     * JSON is used because chapter UUIDs are dynamic object keys in contract version 1.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'policy' => new external_value(PARAM_RAW, 'Serialized chapter access policy'),
        ]);
    }
}
