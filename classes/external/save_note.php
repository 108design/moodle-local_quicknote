<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

namespace local_quicknote\external;

use context_system;
use core_text;
use local_quicknote\local\page_identity;
use local_quicknote\local\screenshot_manager;
use local_quicknote\local\tag_manager;

/**
 * Create or update a private page-specific QuickNote.
 *
 * @package     local_quicknote
 * @copyright   2026 Matheus Mathias
 * @copyright   2026 Andreas Giesen (downstream changes)
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class save_note extends \core_external\external_api {
    public static function execute_parameters(): \core_external\external_function_parameters {
        return new \core_external\external_function_parameters([
            'id' => new \core_external\external_value(PARAM_INT, 'Note id, 0 for new.', VALUE_DEFAULT, 0),
            'courseid' => new \core_external\external_value(PARAM_INT, 'Course id, or 0 outside courses.', VALUE_DEFAULT, 0),
            'content' => new \core_external\external_value(PARAM_RAW, 'Note content.', VALUE_DEFAULT, ''),
            'url' => new \core_external\external_value(PARAM_RAW, 'Moodle page URL.'),
            'pagetitle' => new \core_external\external_value(PARAM_TEXT, 'Page title.', VALUE_DEFAULT, ''),
            'isglobal' => new \core_external\external_value(PARAM_BOOL, 'Show note on every page.', VALUE_DEFAULT, false),
            'quote' => new \core_external\external_value(PARAM_RAW, 'Selected quote text.', VALUE_DEFAULT, ''),
            'quoteurl' => new \core_external\external_value(PARAM_RAW, 'URL pointing to the selected quote.', VALUE_DEFAULT, ''),
            'tags' => new \core_external\external_multiple_structure(
                new \core_external\external_value(PARAM_TAG, 'Tag name.'),
                'User-entered note tags.',
                VALUE_DEFAULT,
                []
            ),
            'updatetags' => new \core_external\external_value(
                PARAM_BOOL,
                'Whether the supplied tags should replace the existing tags.',
                VALUE_DEFAULT,
                false
            ),
        ]);
    }

    public static function execute(
        int $id,
        int $courseid,
        string $content,
        string $url,
        string $pagetitle = '',
        bool $isglobal = false,
        string $quote = '',
        string $quoteurl = '',
        array $tags = [],
        bool $updatetags = false
    ): array {
        global $DB, $USER;

        $params = self::validate_parameters(self::execute_parameters(), [
            'id' => $id,
            'courseid' => $courseid,
            'content' => $content,
            'url' => $url,
            'pagetitle' => $pagetitle,
            'isglobal' => $isglobal,
            'quote' => $quote,
            'quoteurl' => $quoteurl,
            'tags' => $tags,
            'updatetags' => $updatetags,
        ]);

        require_login();
        $context = context_system::instance();
        self::validate_context($context);
        require_capability('local/quicknote:use', $context);

        if ($params['courseid'] < 0 || ($params['courseid'] > 0 && !$DB->record_exists('course', ['id' => $params['courseid']]))) {
            throw new \invalid_parameter_exception('The provided course does not exist.');
        }

        if ($params['url'] === '') {
            $fallback = $params['courseid'] > 0
                ? new \moodle_url('/course/view.php', ['id' => $params['courseid']])
                : new \moodle_url('/');
            $params['url'] = $fallback->out(false);
        }

        $params['url'] = page_identity::sanitise($params['url']);
        if ($params['quoteurl'] !== '') {
            $params['quoteurl'] = page_identity::sanitise($params['quoteurl'], true);
        }

        $existing = null;
        if ($params['id']) {
            $existing = $DB->get_record('local_quicknote_notes', [
                'id' => $params['id'],
                'userid' => $USER->id,
            ], '*', MUST_EXIST);
        }

        $content = core_text::substr($params['content'], 0, 20000);
        $contentformat = FORMAT_MARKDOWN;
        if ($existing) {
            $contentformat = isset($existing->contentformat) ? (int) $existing->contentformat : FORMAT_PLAIN;
            if ((string) ($existing->content ?? '') !== $content) {
                // A legacy plain-text note becomes Markdown only after a real content edit.
                $contentformat = FORMAT_MARKDOWN;
            }
        }

        $record = (object) [
            'userid' => $USER->id,
            'courseid' => $params['courseid'],
            'content' => $content,
            'contentformat' => $contentformat,
            'quote' => core_text::substr($params['quote'], 0, 5000),
            'quoteurl' => core_text::substr($params['quoteurl'], 0, 2048),
            'url' => core_text::substr($params['url'], 0, 2048),
            'pagehash' => page_identity::hash($params['url']),
            'pagetitle' => core_text::substr($params['pagetitle'], 0, 255),
            'isglobal' => (int) $params['isglobal'],
            'timemodified' => time(),
        ];

        if ($existing) {
            $record->id = $existing->id;
            $record->timecreated = $existing->timecreated;
            $DB->update_record('local_quicknote_notes', $record);
        } else {
            $record->timecreated = $record->timemodified;
            $record->id = $DB->insert_record('local_quicknote_notes', $record);
        }

        if ($params['updatetags']) {
            tag_manager::set_for_note((int) $record->id, (int) $USER->id, $params['tags']);
        }

        return self::export_note($DB->get_record('local_quicknote_notes', ['id' => $record->id], '*', MUST_EXIST));
    }

    public static function execute_returns(): \core_external\external_single_structure {
        return self::note_structure();
    }

    public static function note_structure(): \core_external\external_single_structure {
        return new \core_external\external_single_structure([
            'id' => new \core_external\external_value(PARAM_INT, 'Note id.'),
            'userid' => new \core_external\external_value(PARAM_INT, 'Owner user id.'),
            'courseid' => new \core_external\external_value(PARAM_INT, 'Course id or 0.'),
            'content' => new \core_external\external_value(PARAM_RAW, 'Note content.'),
            'contentformat' => new \core_external\external_value(PARAM_INT, 'Stored Moodle text format.'),
            'contenthtml' => new \core_external\external_value(PARAM_RAW, 'Safely rendered note content.'),
            'quote' => new \core_external\external_value(PARAM_RAW, 'Selected quote text.'),
            'hasquote' => new \core_external\external_value(PARAM_BOOL, 'Whether the note contains a quote.'),
            'quotetext' => new \core_external\external_value(PARAM_RAW, 'Quote text.'),
            'quoteurl' => new \core_external\external_value(PARAM_RAW, 'URL pointing to the selected quote.'),
            'url' => new \core_external\external_value(PARAM_RAW, 'Source page URL.'),
            'pagetitle' => new \core_external\external_value(PARAM_TEXT, 'Source page title.'),
            'isglobal' => new \core_external\external_value(PARAM_BOOL, 'Whether the note is global.'),
            'tagsenabled' => new \core_external\external_value(PARAM_BOOL, 'Whether the tag area is enabled.'),
            'tags' => new \core_external\external_multiple_structure(tag_manager::external_structure()),
            'screenshots' => new \core_external\external_multiple_structure(screenshot_manager::external_structure()),
            'timecreated' => new \core_external\external_value(PARAM_INT, 'Creation timestamp.'),
            'timemodified' => new \core_external\external_value(PARAM_INT, 'Last modification timestamp.'),
        ]);
    }

    public static function export_note(\stdClass $note, ?array $tags = null): array {
        $quote = (string) ($note->quote ?? '');
        $content = (string) ($note->content ?? '');
        $contentformat = isset($note->contentformat) ? (int) $note->contentformat : FORMAT_PLAIN;
        if (!in_array($contentformat, [(int) FORMAT_PLAIN, (int) FORMAT_MARKDOWN], true)) {
            $contentformat = (int) FORMAT_PLAIN;
        }
        if ($tags === null) {
            $tags = tag_manager::get_for_note((int) $note->id, (int) $note->userid);
        }
        return [
            'id' => (int) $note->id,
            'userid' => (int) $note->userid,
            'courseid' => (int) $note->courseid,
            'content' => $content,
            'contentformat' => $contentformat,
            'contenthtml' => format_text($content, $contentformat, [
                'context' => context_system::instance(),
                'filter' => false,
            ]),
            'quote' => $quote,
            'hasquote' => trim($quote) !== '',
            'quotetext' => $quote,
            'quoteurl' => (string) ($note->quoteurl ?? ''),
            'url' => (string) ($note->url ?? ''),
            'pagetitle' => (string) ($note->pagetitle ?? ''),
            'isglobal' => !empty($note->isglobal),
            'tagsenabled' => tag_manager::is_enabled(),
            'tags' => array_values($tags),
            'screenshots' => screenshot_manager::get_for_note((int) $note->id),
            'timecreated' => (int) $note->timecreated,
            'timemodified' => (int) $note->timemodified,
        ];
    }
}
