<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

namespace local_quicknote;

use local_quicknote\local\page_identity;

/**
 * Tests for stable page identities.
 *
 * @package     local_quicknote
 * @copyright   2026 Andreas Giesen
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers      \local_quicknote\local\page_identity
 */
final class page_identity_test extends \advanced_testcase {
    public function test_canonicalise_removes_volatile_values_and_sorts_query(): void {
        global $CFG;

        $path = rtrim((string) parse_url($CFG->wwwroot, PHP_URL_PATH), '/');
        $url = rtrim($CFG->wwwroot, '/') . '/admin/settings.php?z=2&sesskey=secret&a=1#section';

        $this->assertSame($path . '/admin/settings.php?a=1&z=2', page_identity::canonicalise($url));
    }

    public function test_hash_ignores_fragment_and_sesskey(): void {
        global $CFG;

        $base = rtrim($CFG->wwwroot, '/') . '/mod/page/view.php?id=7';
        $withvolatiledata = $base . '&sesskey=secret#:~:text=Example';

        $this->assertSame(page_identity::hash($base), page_identity::hash($withvolatiledata));
    }

    public function test_foreign_host_is_rejected(): void {
        $this->expectException(\invalid_parameter_exception::class);
        page_identity::canonicalise('https://example.invalid/admin/index.php');
    }

    public function test_legacy_hash_survives_host_migration(): void {
        global $CFG;

        $current = rtrim($CFG->wwwroot, '/') . '/admin/index.php?a=1';
        $legacy = 'http://old-moodle.example.invalid' . parse_url($current, PHP_URL_PATH) . '?a=1&sesskey=old';

        $this->assertSame(page_identity::hash($current), page_identity::legacy_hash($legacy));
    }
}
