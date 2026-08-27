<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

namespace local_quicknote\external;

use context_system;
use local_quicknote\local\page_identity;

/**
 * Retrieve the current user's notes for one page plus their global notes.
 *
 * @package     local_quicknote
 * @copyright   2026 Matheus Mathias
 * @copyright   2026 Andreas Giesen (downstream changes)
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class get_notes extends \core_external\external_api {
    public static function execute_parameters(): \core_external\external_function_parameters {
        return new \core_external\external_function_parameters([
            'courseid' => new \core_external\external_value(PARAM_INT, 'Course id or 0.', VALUE_DEFAULT, 0),
            'pageurl' => new \core_external\external_value(PARAM_RAW, 'Current Moodle page URL.', VALUE_DEFAULT, ''),
        ]);
    }

    public static function execute(int $courseid = 0, string $pageurl = ''): array {
        global $DB, $USER;
        $params = self::validate_parameters(self::execute_parameters(), ['courseid' => $courseid, 'pageurl' => $pageurl]);

        require_login();
        $context = context_system::instance();
        self::validate_context($context);
        require_capability('local/quicknote:use', $context);

        $queryparams = ['userid' => $USER->id];
        if ($params['pageurl'] !== '') {
            $queryparams['pagehash'] = page_identity::hash($params['pageurl']);
            $select = 'userid = :userid AND (pagehash = :pagehash OR isglobal = 1)';
        } else {
            $queryparams['courseid'] = $params['courseid'];
            $select = 'userid = :userid AND (courseid = :courseid OR isglobal = 1)';
        }

        $records = $DB->get_records_select(
            'local_quicknote_notes', $select, $queryparams, 'isglobal DESC, timemodified DESC, id DESC'
        );
        return array_map([save_note::class, 'export_note'], array_values($records));
    }

    public static function execute_returns(): \core_external\external_multiple_structure {
        return new \core_external\external_multiple_structure(save_note::note_structure());
    }
}
