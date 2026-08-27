<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Plugin version and other meta-data are defined here.
 *
 * @package     local_quicknote
 * @copyright   2026 Matheus Mathias
 * @copyright   2026 Andreas Giesen (downstream changes)
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $settings = new admin_settingpage('local_quicknote', get_string('pluginname', 'local_quicknote'));

    // Adds position option.
    $settings->add(new admin_setting_configselect(
        'local_quicknote/position',
        get_string('position', 'local_quicknote'),
        get_string('position_desc', 'local_quicknote'),
        'right',
        [
            'right' => get_string('position_right', 'local_quicknote'),
            'left'  => get_string('position_left', 'local_quicknote'),
        ]
    ));

    // Adds notes per page option.
    $settings->add(new admin_setting_configselect(
        'local_quicknote/perpage',
        get_string('perpage', 'local_quicknote'),
        get_string('perpage_desc', 'local_quicknote'),
        12,
        [
            12 => '12',
            24 => '24',
            48 => '48',
            0  => get_string('all', 'core'),
        ]
    ));

    $ADMIN->add('localplugins', $settings);
}
