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
                var activeRequest = null;
                var searchForm = searchInput.form;
                var center = document.querySelector('.local-quicknote-center');
                var replaceRegion = function(nextDocument, selector) {
                    var currentRegion = document.querySelector(selector);
                    var nextRegion = nextDocument.querySelector(selector);
                    if (currentRegion && nextRegion) {
                        currentRegion.innerHTML = nextRegion.innerHTML;
                        if (nextRegion.hasAttribute('hidden')) {
                            currentRegion.setAttribute('hidden', 'hidden');
                        } else {
                            currentRegion.removeAttribute('hidden');
                        }
                    }
                };
                var submitSearch = function(force) {
                    var nextSearch = searchInput.value.trim();
                    if ((!force && nextSearch === submittedSearch) || !searchForm || !center) {
                        return;
                    }
                    submittedSearch = nextSearch;
                    if (activeRequest) {
                        activeRequest.abort();
                    }
                    var request = new AbortController();
                    activeRequest = request;

                    var url = new URL(searchForm.action, window.location.href);
                    new FormData(searchForm).forEach(function(value, name) {
                        if (name === 'searchterm') {
                            value = nextSearch;
                        }
                        if (String(value).length > 0 && String(value) !== '0') {
                            url.searchParams.set(name, value);
                        } else {
                            url.searchParams.delete(name);
                        }
                    });
                    center.setAttribute('aria-busy', 'true');

                    fetch(url.toString(), {
                        credentials: 'same-origin',
                        headers: {'X-Requested-With': 'XMLHttpRequest'},
                        signal: request.signal
                    }).then(function(response) {
                        if (!response.ok) {
                            throw new Error('QuickNote search request failed.');
                        }
                        return response.text();
                    }).then(function(html) {
                        var nextDocument = new DOMParser().parseFromString(html, 'text/html');
                        replaceRegion(nextDocument, '[data-region="quicknote-results"]');
                        replaceRegion(nextDocument, '[data-region="quicknote-pagination"]');
                        replaceRegion(nextDocument, '[data-region="quicknote-exports"]');
                        window.history.replaceState({}, '', url.toString());
                    }).catch(function(error) {
                        if (error.name !== 'AbortError') {
                            window.location.assign(url.toString());
                        }
                    }).finally(function() {
                        if (activeRequest === request) {
                            center.removeAttribute('aria-busy');
                            activeRequest = null;
                        }
                    });
                };

                searchInput.addEventListener('input', function() {
                    if (this.value.trim().length > 0) {
                        clearSearchBtn.removeAttribute('hidden');
                    } else {
                        clearSearchBtn.setAttribute('hidden', 'hidden');
                    }

                    window.clearTimeout(searchTimer);
                    searchTimer = window.setTimeout(function() {
                        submitSearch(false);
                    }, 400);
                });

                clearSearchBtn.addEventListener('click', function() {
                    window.clearTimeout(searchTimer);
                    searchInput.value = '';
                    clearSearchBtn.setAttribute('hidden', 'hidden');
                    submitSearch(true);
                    searchInput.focus();
                });

                searchForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    window.clearTimeout(searchTimer);
                    submitSearch(true);
                });
            }

            document.addEventListener('submit', function(e) {
                var form = e.target.closest('.local-quicknote-center__delete-form');
                if (form && !window.confirm(form.getAttribute('data-delete-confirm'))) {
                    e.preventDefault();
                }
            });
        }
    };
});
