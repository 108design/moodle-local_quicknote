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
 * @copyright   2026 Andreas Giesen (downstream changes)
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define([], function() {
    return {
        init: function() {
            var bindFilter = function(select) {
                var isKeyboardNav = false;
                if (!select) {
                    return;
                }
                select.addEventListener('keydown', function(e) {
                    // Up, Down, Left, Right arrows.
                    if (['ArrowUp', 'ArrowDown', 'ArrowLeft', 'ArrowRight'].indexOf(e.key) !== -1) {
                        isKeyboardNav = true;
                    }
                    if (e.key === 'Enter' && this.form) {
                        this.form.submit();
                    }
                });

                select.addEventListener('mousedown', function() {
                    isKeyboardNav = false;
                });

                select.addEventListener('change', function() {
                    if (!isKeyboardNav && this.form) {
                        this.form.submit();
                    }
                    isKeyboardNav = false;
                });
            };

            bindFilter(document.getElementById('coursefilter'));
            bindFilter(document.getElementById('tagfilter'));

            var searchInput = document.getElementById('searchterm');
            var clearSearchBtn = document.getElementById('clearsearch');

            if (searchInput && clearSearchBtn) {
                var searchTimer = null;
                var submittedSearch = searchInput.value.trim();
                var submitSearch = function() {
                    var nextSearch = searchInput.value.trim();
                    if (nextSearch === submittedSearch || !searchInput.form) {
                        return;
                    }
                    submittedSearch = nextSearch;
                    searchInput.form.submit();
                };

                searchInput.addEventListener('input', function() {
                    if (this.value.trim().length > 0) {
                        clearSearchBtn.removeAttribute('hidden');
                    } else {
                        clearSearchBtn.setAttribute('hidden', 'hidden');
                    }

                    window.clearTimeout(searchTimer);
                    searchTimer = window.setTimeout(submitSearch, 400);
                });

                clearSearchBtn.addEventListener('click', function() {
                    window.clearTimeout(searchTimer);
                    searchInput.value = '';
                    clearSearchBtn.setAttribute('hidden', 'hidden');
                    submitSearch();
                });
            }

            document.querySelectorAll('.local-quicknote-center__delete-form').forEach(function(form) {
                form.addEventListener('submit', function(e) {
                    if (!window.confirm(form.getAttribute('data-delete-confirm'))) {
                        e.preventDefault();
                    }
                });
            });
        }
    };
});
