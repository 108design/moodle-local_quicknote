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
 * @module      local_quicknote/view
 * @copyright   2026 Matheus Mathias
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define([], function() {
    return {
        init: function() {
            var select = document.getElementById('coursefilter');
            var isKeyboardNav = false;

            if (!select) {
                return;
            }

            select.addEventListener('keydown', function(e) {
                // Up, Down, Left, Right arrows
                if (['ArrowUp', 'ArrowDown', 'ArrowLeft', 'ArrowRight'].indexOf(e.key) !== -1) {
                    isKeyboardNav = true;
                }
                // Enter key
                if (e.key === 'Enter') {
                    if (this.form) {
                        this.form.submit();
                    }
                }
            });

            select.addEventListener('mousedown', function() {
                isKeyboardNav = false;
            });

            select.addEventListener('change', function() {
                if (!isKeyboardNav) {
                    if (this.form) {
                        this.form.submit();
                    }
                }
                isKeyboardNav = false; // Reset for next interaction
            });
        }
    };
});
