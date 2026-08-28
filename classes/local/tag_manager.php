<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

namespace local_quicknote\local;

use context_system;
use core_tag_area;
use core_tag_tag;

/**
 * User-scoped access to the Moodle Tag API for QuickNote notes.
 *
 * @package     local_quicknote
 * @copyright   2026 Andreas Giesen
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class tag_manager {
    public const COMPONENT = 'local_quicknote';
    public const ITEMTYPE = 'local_quicknote_notes';
    public const MAX_TAGS = 20;
    public const MAX_TAG_LENGTH = 100;

    /** Whether the QuickNote tag area is currently enabled. */
    public static function is_enabled(): bool {
        return core_tag_area::is_enabled(self::COMPONENT, self::ITEMTYPE) === true;
    }

    /**
     * Normalise user-entered tag names while preserving their display spelling.
     *
     * @param array $tags Raw tag names.
     * @return string[]
     */
    public static function normalise(array $tags): array {
        $result = [];
        $seen = [];

        foreach ($tags as $tag) {
            $tag = trim(clean_param((string) $tag, PARAM_TAG));
            $tag = \core_text::substr($tag, 0, self::MAX_TAG_LENGTH);
            if ($tag === '') {
                continue;
            }

            $key = \core_text::strtolower($tag);
            if (isset($seen[$key])) {
                continue;
            }

            $seen[$key] = true;
            $result[] = $tag;
            if (count($result) >= self::MAX_TAGS) {
                break;
            }
        }

        return $result;
    }

    /** Set the current user's tags for one owned note. */
    public static function set_for_note(int $noteid, int $userid, array $tags): void {
        if (!self::is_enabled()) {
            return;
        }

        core_tag_tag::set_item_tags(
            self::COMPONENT,
            self::ITEMTYPE,
            $noteid,
            context_system::instance(),
            self::normalise($tags),
            $userid
        );
    }

    /** Remove every user-specific tag instance attached to one note. */
    public static function remove_for_note(int $noteid, int $userid): void {
        core_tag_tag::remove_all_item_tags(self::COMPONENT, self::ITEMTYPE, $noteid, $userid);
    }

    /**
     * Return externally safe tag data for several notes.
     *
     * @param int[] $noteids Note ids.
     * @param int $userid Tag-instance owner.
     * @return array<int, array<int, array{id: int, name: string}>>
     */
    public static function get_for_notes(array $noteids, int $userid): array {
        $noteids = array_values(array_unique(array_map('intval', $noteids)));
        $result = array_fill_keys($noteids, []);
        if (!$noteids || !self::is_enabled()) {
            return $result;
        }

        $items = core_tag_tag::get_items_tags(
            self::COMPONENT,
            self::ITEMTYPE,
            $noteids,
            core_tag_tag::BOTH_STANDARD_AND_NOT,
            $userid
        );

        foreach ($items as $noteid => $tags) {
            foreach ($tags as $tag) {
                $result[(int) $noteid][] = [
                    'id' => (int) $tag->id,
                    'name' => $tag->get_display_name(false),
                ];
            }
        }

        return $result;
    }

    /** Return externally safe tag data for one note. */
    public static function get_for_note(int $noteid, int $userid): array {
        $items = self::get_for_notes([$noteid], $userid);
        return $items[$noteid] ?? [];
    }

    /** Shared external-service structure for a tag. */
    public static function external_structure(): \core_external\external_single_structure {
        return new \core_external\external_single_structure([
            'id' => new \core_external\external_value(PARAM_INT, 'Tag id.'),
            'name' => new \core_external\external_value(PARAM_TEXT, 'Tag display name.'),
        ]);
    }
}
