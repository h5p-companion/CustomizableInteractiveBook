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

namespace local_h5pchapteraccess\dto;

/**
 * Immutable chapter entry from an H5P book manifest.
 *
 * @package    local_h5pchapteraccess
 * @copyright  2026 Luiz Gustavo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class chapter implements \JsonSerializable {

    /** @var string Chapter subContentId or controlled legacy fallback. */
    private string $id;

    /** @var string Chapter title. */
    private string $title;

    /** @var int Original zero-based position. */
    private int $position;

    /** @var bool Whether the ID is a stable H5P subContentId. */
    private bool $stable;

    /**
     * Constructor.
     *
     * @param string $id Chapter identifier
     * @param string $title Chapter title
     * @param int $position Original zero-based position
     * @param bool $stable Whether the ID is stable
     */
    public function __construct(string $id, string $title, int $position, bool $stable) {
        if ($id === '') {
            throw new \InvalidArgumentException('Chapter ID cannot be empty.');
        }
        if ($position < 0) {
            throw new \InvalidArgumentException('Chapter position cannot be negative.');
        }

        $this->id = $id;
        $this->title = $title;
        $this->position = $position;
        $this->stable = $stable;
    }

    /**
     * Get the chapter identifier.
     *
     * @return string
     */
    public function get_id(): string {
        return $this->id;
    }

    /**
     * Get the chapter title.
     *
     * @return string
     */
    public function get_title(): string {
        return $this->title;
    }

    /**
     * Get the original zero-based position.
     *
     * @return int
     */
    public function get_position(): int {
        return $this->position;
    }

    /**
     * Whether the identifier is stable.
     *
     * @return bool
     */
    public function is_stable(): bool {
        return $this->stable;
    }

    /**
     * Export the chapter using the H5P host contract field names.
     *
     * @return array
     */
    public function to_array(): array {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'position' => $this->position,
            'stable' => $this->stable,
        ];
    }

    /**
     * Serialize the DTO.
     *
     * @return array
     */
    public function jsonSerialize(): array {
        return $this->to_array();
    }
}
