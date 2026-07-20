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

use local_h5pchapteraccess\dto\chapter;
use local_h5pchapteraccess\dto\manifest;
use local_h5pchapteraccess\service\deployment_diagnostics;
use local_h5pchapteraccess\service\manifest_extractor;

/**
 * Tests for portable deployment and configuration diagnostics.
 *
 * @package    local_h5pchapteraccess
 * @category   test
 * @copyright  2026 Luiz Gustavo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class deployment_diagnostics_test extends \basic_testcase {

    /** A compatible runtime with an enabled restriction is ready. */
    public function test_compatible_configured_deployment_is_ready(): void {
        $diagnostics = (new deployment_diagnostics())->inspect(
            $this->manifest(['major' => 1, 'minor' => 0, 'patch' => 35]),
            (object) ['enabled' => 1],
            [
                $this->record('open'),
                $this->record('locked'),
                $this->record('conditional'),
            ],
            true
        );

        $this->assertTrue($diagnostics['librarycompatible']);
        $this->assertTrue($diagnostics['ready']);
        $this->assertSame(1, $diagnostics['opencount']);
        $this->assertSame(1, $diagnostics['lockedcount']);
        $this->assertSame(1, $diagnostics['conditionalcount']);
        $this->assertSame(2, $diagnostics['restrictedcount']);
    }

    /** An older library is diagnosed even when rules exist. */
    public function test_old_h5p_runtime_is_not_ready(): void {
        $diagnostics = (new deployment_diagnostics())->inspect(
            $this->manifest(['major' => 1, 'minor' => 0, 'patch' => 31]),
            (object) ['enabled' => 1],
            [$this->record('locked')],
            true
        );

        $this->assertFalse($diagnostics['librarycompatible']);
        $this->assertFalse($diagnostics['ready']);
        $this->assertSame('1.0.31', $diagnostics['libraryversion']);
    }

    /** Missing rules and generated bridge are both visible without policy evaluation. */
    public function test_incomplete_destination_installation_is_not_ready(): void {
        $diagnostics = (new deployment_diagnostics())->inspect(
            $this->manifest(['major' => 1, 'minor' => 0, 'patch' => 35]),
            (object) ['enabled' => 0],
            [$this->record('open', false)],
            false
        );

        $this->assertFalse($diagnostics['bridgeassetavailable']);
        $this->assertFalse($diagnostics['integrationenabled']);
        $this->assertSame(0, $diagnostics['restrictedcount']);
        $this->assertSame(1, $diagnostics['unstablecount']);
        $this->assertFalse($diagnostics['ready']);
    }

    /**
     * Build a versioned manifest.
     *
     * @param array $version H5P version
     * @return manifest
     */
    private function manifest(array $version): manifest {
        return new manifest(
            10,
            manifest_extractor::MACHINE_NAME,
            20,
            'content-hash',
            [new chapter('uuid-a', 'Chapter', 0, true)],
            $version
        );
    }

    /**
     * Build an active stored chapter record.
     *
     * @param string $mode Access mode
     * @param bool $stable Whether the identifier is stable
     * @return \stdClass
     */
    private function record(string $mode, bool $stable = true): \stdClass {
        return (object) [
            'active' => 1,
            'stableid' => $stable ? 1 : 0,
            'accessmode' => $mode,
        ];
    }
}
