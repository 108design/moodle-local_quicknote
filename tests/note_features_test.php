<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

namespace local_quicknote;

use local_quicknote\external\delete_note;
use local_quicknote\external\save_note;
use local_quicknote\local\tag_manager;

/**
 * Tests Markdown migration and private tags.
 *
 * @package     local_quicknote
 * @copyright   2026 Andreas Giesen
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers      \local_quicknote\external\save_note
 * @covers      \local_quicknote\local\tag_manager
 */
final class note_features_test extends \advanced_testcase {
    public function test_plain_note_only_becomes_markdown_after_content_change(): void {
        global $CFG, $DB;

        $this->resetAfterTest();
        $this->setAdminUser();
        $CFG->usetags = true;
        \core_tag_area::reset_definitions_for_component('local_quicknote');

        $course = $this->getDataGenerator()->create_course();
        $url = (new \moodle_url('/course/view.php', ['id' => $course->id]))->out(false);
        $created = save_note::execute(
            0,
            (int) $course->id,
            'Legacy text',
            $url,
            'Test course',
            false,
            '',
            '',
            ['Review'],
            true
        );

        $DB->set_field('local_quicknote_notes', 'contentformat', FORMAT_PLAIN, ['id' => $created['id']]);
        $toggled = save_note::execute(
            $created['id'],
            (int) $course->id,
            'Legacy text',
            $url,
            'Test course',
            true
        );
        $this->assertSame((int) FORMAT_PLAIN, $toggled['contentformat']);

        $edited = save_note::execute(
            $created['id'],
            (int) $course->id,
            "Legacy text\n\n**Bold**",
            $url,
            'Test course',
            true
        );
        $this->assertSame((int) FORMAT_MARKDOWN, $edited['contentformat']);
        $this->assertStringContainsString('<strong>Bold</strong>', $edited['contenthtml']);
        $this->assertSame('Review', $edited['tags'][0]['name']);

        delete_note::execute($created['id']);
        $this->assertFalse($DB->record_exists('local_quicknote_notes', ['id' => $created['id']]));
        $this->assertFalse($DB->record_exists('tag_instance', [
            'component' => tag_manager::COMPONENT,
            'itemtype' => tag_manager::ITEMTYPE,
            'itemid' => $created['id'],
        ]));
    }

    public function test_tag_normalisation_is_bounded_and_deduplicated(): void {
        $tags = tag_manager::normalise([' Review ', 'review', '', 'Accessibility']);
        $this->assertSame(['Review', 'Accessibility'], $tags);
    }
}
