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
 * Immutable manifest extracted from an H5P Customizable Interactive Book.
 *
 * @package    local_h5pchapteraccess
 * @copyright  2026 Luiz Gustavo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class manifest implements \JsonSerializable {

    /** @var int Moodle course module ID used internally by the synchronizer. */
    private int $cmid;

    /** @var string H5P main library machine name. */
    private string $machinename;

    /** @var int Moodle core_h5p content ID. */
    private int $contentid;

    /** @var string File content hash. */
    private string $contenthash;

    /** @var chapter[] Ordered chapter entries. */
    private array $chapters;

    /** @var string Deterministic SHA-256 hash of chapter IDs, titles and positions. */
    private string $manifesthash;

    /**
     * Constructor.
     *
     * @param int $cmid Moodle course module ID
     * @param string $machinename H5P main library machine name
     * @param int $contentid Moodle core_h5p content ID
     * @param string $contenthash File content hash
     * @param chapter[] $chapters Ordered chapter entries
     */
    public function __construct(
        int $cmid,
        string $machinename,
        int $contentid,
        string $contenthash,
        array $chapters
    ) {
        if ($cmid <= 0 || $contentid <= 0) {
            throw new \InvalidArgumentException('Manifest identifiers must be positive integers.');
        }
        if ($machinename === '' || $contenthash === '') {
            throw new \InvalidArgumentException('Manifest machine name and content hash are required.');
        }

        $seenids = [];
        foreach ($chapters as $chapter) {
            if (!$chapter instanceof chapter) {
                throw new \InvalidArgumentException('Manifest chapters must be chapter DTO instances.');
            }
            if (isset($seenids[$chapter->get_id()])) {
                throw new \InvalidArgumentException('Manifest chapter IDs must be unique.');
            }
            $seenids[$chapter->get_id()] = true;
        }

        $this->cmid = $cmid;
        $this->machinename = $machinename;
        $this->contentid = $contentid;
        $this->contenthash = $contenthash;
        $this->chapters = array_values($chapters);
        $this->manifesthash = $this->calculate_hash();
    }

    /** @return int Moodle course module ID. */
    public function get_cmid(): int {
        return $this->cmid;
    }

    /** @return string H5P main library machine name. */
    public function get_machine_name(): string {
        return $this->machinename;
    }

    /** @return int Moodle core_h5p content ID. */
    public function get_content_id(): int {
        return $this->contentid;
    }

    /** @return string File content hash. */
    public function get_content_hash(): string {
        return $this->contenthash;
    }

    /**
     * Get an ordered defensive copy of the chapter list.
     *
     * @return chapter[]
     */
    public function get_chapters(): array {
        return array_values($this->chapters);
    }

    /** @return string Deterministic manifest hash. */
    public function get_manifest_hash(): string {
        return $this->manifesthash;
    }

    /**
     * Export the public manifest fields.
     *
     * The cmid is intentionally internal and is not part of the browser contract.
     *
     * @return array
     */
    public function to_array(): array {
        return [
            'machineName' => $this->machinename,
            'contentId' => $this->contentid,
            'contentHash' => $this->contenthash,
            'manifestHash' => $this->manifesthash,
            'chapters' => array_map(static fn(chapter $chapter): array => $chapter->to_array(), $this->chapters),
        ];
    }

    /** @return array Serialized manifest. */
    public function jsonSerialize(): array {
        return $this->to_array();
    }

    /**
     * Calculate the deterministic chapter manifest hash.
     *
     * @return string
     */
    private function calculate_hash(): string {
        $canonical = array_map(static function(chapter $chapter): array {
            return [
                'id' => $chapter->get_id(),
                'title' => $chapter->get_title(),
                'position' => $chapter->get_position(),
            ];
        }, $this->chapters);

        return hash('sha256', json_encode(
            $canonical,
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
        ));
    }
}
