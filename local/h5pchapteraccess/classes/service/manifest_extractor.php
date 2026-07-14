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

use core_h5p\api;
use core_h5p\factory;
use local_h5pchapteraccess\dto\chapter;
use local_h5pchapteraccess\dto\manifest;
use local_h5pchapteraccess\exception\invalid_activity_exception;
use local_h5pchapteraccess\exception\unsupported_content_exception;
use mod_h5pactivity\local\manager;

/**
 * Locates a Moodle H5P activity and extracts its chapter manifest.
 *
 * @package    local_h5pchapteraccess
 * @copyright  2026 Luiz Gustavo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class manifest_extractor {

    /** Supported main library. */
    public const MACHINE_NAME = 'H5P.CustomizableInteractiveBook';

    /** Runtime-only title used when H5P metadata has no usable title. */
    public const FALLBACK_TITLE = 'Untitled chapter';

    /** Maximum size of chapteruuid in the plugin schema. */
    private const MAX_CHAPTER_ID_LENGTH = 128;

    /** @var factory core_h5p factory. */
    private factory $factory;

    /** @var manifest_cache Structural manifest cache. */
    private manifest_cache $cache;

    /**
     * Constructor.
     *
     * @param factory|null $factory core_h5p factory, primarily for tests
     * @param manifest_cache|null $cache Structural manifest cache
     */
    public function __construct(?factory $factory = null, ?manifest_cache $cache = null) {
        $this->factory = $factory ?? new factory();
        $this->cache = $cache ?? new manifest_cache();
    }

    /**
     * Extract a manifest from one Moodle course module.
     *
     * @param int $cmid Course module ID
     * @return manifest
     * @throws invalid_activity_exception
     * @throws unsupported_content_exception
     */
    public function extract(int $cmid): manifest {
        return $this->extract_manifest($cmid, true);
    }

    /**
     * Extract a manifest during a trusted Moodle restore operation.
     *
     * This bypasses only the interactive login/capability checks. Module type,
     * H5P instance, package and library validation remain identical.
     *
     * @param int $cmid Newly restored course module ID
     * @return manifest
     */
    public function extract_after_restore(int $cmid): manifest {
        return $this->extract_manifest($cmid, false);
    }

    /**
     * Shared extraction implementation.
     *
     * @param int $cmid Course module ID
     * @param bool $authorize Whether to perform interactive access checks
     * @return manifest
     */
    private function extract_manifest(int $cmid, bool $authorize): manifest {
        if ($cmid <= 0) {
            throw new invalid_activity_exception(invalid_activity_exception::CM_NOT_FOUND, $cmid);
        }

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

        $context = \context_module::instance($cm->id);
        if ($authorize) {
            try {
                require_login($course, true, $cm);
                require_capability('mod/h5pactivity:view', $context);
            } catch (\moodle_exception $exception) {
                throw new invalid_activity_exception(
                    invalid_activity_exception::ACCESS_DENIED,
                    $cmid,
                    $exception->getMessage()
                );
            }
        }

        try {
            // The public manager API validates and loads the h5pactivity instance.
            manager::create_from_coursemodule($cm)->get_instance();
        } catch (\dml_exception $exception) {
            throw new invalid_activity_exception(
                invalid_activity_exception::INSTANCE_NOT_FOUND,
                $cmid,
                $exception->getMessage()
            );
        }

        $file = $this->get_package_file($context);
        $fileurl = \moodle_url::make_pluginfile_url(
            $file->get_contextid(),
            $file->get_component(),
            $file->get_filearea(),
            $file->get_itemid(),
            $file->get_filepath(),
            $file->get_filename(),
            false
        );

        // This public API resolves referenced files, including Content Bank aliases,
        // while retaining the normal pluginfile capability checks.
        [$originalfile, $h5p] = api::get_original_content_from_pluginfile_url(
            $fileurl->out(false),
            true,
            false
        );

        if (!$originalfile) {
            throw new invalid_activity_exception(invalid_activity_exception::PACKAGE_NOT_FOUND, $cmid);
        }
        if (!$h5p) {
            throw new invalid_activity_exception(invalid_activity_exception::H5P_NOT_FOUND, $cmid);
        }

        try {
            $library = api::get_library((int) $h5p->mainlibraryid);
        } catch (\dml_missing_record_exception $exception) {
            throw new invalid_activity_exception(
                invalid_activity_exception::H5P_NOT_FOUND,
                $cmid,
                $exception->getMessage()
            );
        }
        if ($library->machinename !== self::MACHINE_NAME) {
            throw new unsupported_content_exception(
                unsupported_content_exception::INCOMPATIBLE_LIBRARY,
                $library->machinename
            );
        }

        $contenthash = $originalfile->get_contenthash();
        $cached = $this->cache->get(
            $cmid,
            (int) $h5p->id,
            $contenthash,
            $library->machinename
        );
        if ($cached !== null) {
            return $cached;
        }

        $json = null;
        try {
            $content = $this->factory->get_core()->loadContent((int) $h5p->id);
            if (is_array($content) && isset($content['params']) && is_string($content['params'])) {
                $json = $content['params'];
            }
        } catch (\Throwable $exception) {
            // A missing/unreadable API payload is handled by the documented package fallback below.
            $json = null;
        }

        if ($json === null) {
            $json = $this->extract_content_json_from_package($originalfile);
        }

        $manifest = $this->create_manifest_from_json(
            $cmid,
            (int) $h5p->id,
            $contenthash,
            $library->machinename,
            $json
        );
        $this->cache->set($manifest);
        return $manifest;
    }

    /**
     * Parse a trusted H5P identity and its parameters into a manifest.
     *
     * This public lower-level operation keeps parsing deterministic and independently testable.
     * Normal application code should call {@see self::extract()}.
     *
     * @param int $cmid Course module ID
     * @param int $contentid core_h5p content ID
     * @param string $contenthash Package content hash
     * @param string $machinename Main library machine name
     * @param string $json H5P parameters JSON
     * @return manifest
     * @throws unsupported_content_exception
     */
    public function create_manifest_from_json(
        int $cmid,
        int $contentid,
        string $contenthash,
        string $machinename,
        string $json
    ): manifest {
        if ($machinename !== self::MACHINE_NAME) {
            throw new unsupported_content_exception(
                unsupported_content_exception::INCOMPATIBLE_LIBRARY,
                $machinename
            );
        }

        try {
            $parameters = json_decode($json, false, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            throw new unsupported_content_exception(
                unsupported_content_exception::INVALID_JSON,
                null,
                $exception->getMessage()
            );
        }

        if (!is_object($parameters)) {
            throw new unsupported_content_exception(unsupported_content_exception::INVALID_JSON);
        }

        // core_h5p normally returns params directly. Accept the stored wrapper as a defensive compatibility path.
        if (!isset($parameters->config) && isset($parameters->params) && is_object($parameters->params)) {
            $parameters = $parameters->params;
        }

        if (!isset($parameters->config)
                || !is_object($parameters->config)
                || !isset($parameters->config->chapters)
                || !is_array($parameters->config->chapters)
                || $parameters->config->chapters === []) {
            throw new unsupported_content_exception(unsupported_content_exception::INVALID_CHAPTERS);
        }

        $chapters = [];
        $seenids = [];
        foreach ($parameters->config->chapters as $position => $chapterdata) {
            if (!is_object($chapterdata)) {
                throw new unsupported_content_exception(
                    unsupported_content_exception::INVALID_CHAPTERS,
                    $position
                );
            }

            $configuredid = is_string($chapterdata->subContentId ?? null)
                ? trim($chapterdata->subContentId)
                : '';
            $stable = $configuredid !== '';
            $chapterid = $stable ? $configuredid : 'legacy-position-' . $position;

            if (\core_text::strlen($chapterid) > self::MAX_CHAPTER_ID_LENGTH) {
                throw new unsupported_content_exception(
                    unsupported_content_exception::INVALID_CHAPTERS,
                    $position,
                    'Chapter ID exceeds the 128-character storage limit.'
                );
            }
            if (isset($seenids[$chapterid])) {
                throw new unsupported_content_exception(
                    unsupported_content_exception::DUPLICATE_CHAPTER_ID,
                    $chapterid
                );
            }
            $seenids[$chapterid] = true;

            $title = '';
            if (isset($chapterdata->metadata)
                    && is_object($chapterdata->metadata)
                    && is_string($chapterdata->metadata->title ?? null)) {
                $title = trim($chapterdata->metadata->title);
            }

            $chapters[] = new chapter(
                $chapterid,
                $title !== '' ? $title : self::FALLBACK_TITLE,
                (int) $position,
                $stable
            );
        }

        return new manifest($cmid, $machinename, $contentid, $contenthash, $chapters);
    }

    /**
     * Find the one package file stored by mod_h5pactivity.
     *
     * @param \context_module $context Activity context
     * @return \stored_file
     * @throws invalid_activity_exception
     */
    private function get_package_file(\context_module $context): \stored_file {
        $files = get_file_storage()->get_area_files(
            $context->id,
            'mod_h5pactivity',
            'package',
            0,
            'sortorder, itemid, filepath, filename',
            false
        );
        $file = reset($files);
        if (!$file) {
            throw new invalid_activity_exception(
                invalid_activity_exception::PACKAGE_NOT_FOUND,
                $context->instanceid
            );
        }
        return $file;
    }

    /**
     * Read only content/content.json from the H5P package using Moodle's zip_packer.
     *
     * @param \stored_file $file Original H5P package file
     * @return string
     * @throws unsupported_content_exception
     */
    private function extract_content_json_from_package(\stored_file $file): string {
        $archivepath = $file->copy_content_to_temp('local_h5pchapteraccess', 'h5p_');
        $extractpath = make_temp_directory('local_h5pchapteraccess/extract_' . random_string(20));

        if (!$archivepath || !$extractpath) {
            throw new unsupported_content_exception(unsupported_content_exception::JSON_UNAVAILABLE);
        }

        try {
            $packer = get_file_packer('application/zip');
            $result = $packer->extract_to_pathname(
                $archivepath,
                $extractpath,
                ['content/content.json'],
                null,
                true
            );
            $jsonpath = $extractpath . '/content/content.json';
            if (!$result || !is_readable($jsonpath)) {
                throw new unsupported_content_exception(unsupported_content_exception::JSON_UNAVAILABLE);
            }

            $json = file_get_contents($jsonpath);
            if ($json === false) {
                throw new unsupported_content_exception(unsupported_content_exception::JSON_UNAVAILABLE);
            }
            return $json;
        } finally {
            @unlink($archivepath);
            remove_dir($extractpath);
        }
    }
}
