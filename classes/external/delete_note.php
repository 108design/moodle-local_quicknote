<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

namespace local_quicknote\external;

use context_system;
use local_quicknote\local\screenshot_manager;
use local_quicknote\local\tag_manager;

/**
 * Delete one owned private note and its screenshots.
 *
 * @package     local_quicknote
 * @copyright   2026 Matheus Mathias
 * @copyright   2026 Andreas Giesen (downstream changes)
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class delete_note extends \core_external\external_api {
    public static function execute_parameters(): \core_external\external_function_parameters {
        return new \core_external\external_function_parameters([
            'noteid' => new \core_external\external_value(PARAM_INT, 'Note id to delete.'),
        ]);
    }

    public static function execute(int $noteid): array {
        global $DB, $USER;
        $params = self::validate_parameters(self::execute_parameters(), ['noteid' => $noteid]);

        require_login();
        $context = context_system::instance();
        self::validate_context($context);
        require_capability('local/quicknote:use', $context);

        $note = $DB->get_record('local_quicknote_notes', [
            'id' => $params['noteid'],
            'userid' => $USER->id,
        ], '*', MUST_EXIST);
        screenshot_manager::delete_for_note((int) $note->id);
        tag_manager::remove_for_note((int) $note->id, (int) $USER->id);
        $DB->delete_records('local_quicknote_notes', ['id' => $note->id]);
        return ['noteid' => (int) $note->id, 'deleted' => true];
    }

    public static function execute_returns(): \core_external\external_single_structure {
        return new \core_external\external_single_structure([
            'noteid' => new \core_external\external_value(PARAM_INT, 'Deleted note id.'),
            'deleted' => new \core_external\external_value(PARAM_BOOL, 'Whether the note was deleted.'),
        ]);
    }
}
