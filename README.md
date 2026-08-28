# Quick Note #

> **108design downstream fork:** This copy keeps the original component name for in-place upgrades and remains licensed under GNU GPL v3 or later. The original work and copyright notices by Matheus Mathias and other upstream contributors are retained. Andreas Giesen is credited only for the downstream changes beginning with `0.10.0-108design.1`.

Designed for the native Boost experience in Moodle 4.4 and 4.5+, QuickNote helps students capture important excerpts while reading course materials, connect those excerpts to personal reflections, and return to the exact place where learning happened. For teachers and administrators, it provides simple controls to decide when and where the tool is available.

Instead of forcing learners to copy text into external apps, QuickNote keeps the study workflow inside Moodle. Students can highlight a passage, save it instantly as a quote, add their own interpretation, and revisit the original context later through browser text-fragment navigation. The result is a cleaner, more focused note-taking experience that supports active reading, revision, and deeper engagement with course content.

<img width="1155" height="949" alt="demo-quicknote" src="https://github.com/user-attachments/assets/07d02bb9-0a81-49fe-837e-10d1b4672b72" />

## ✨ Features

- **Granular Control:** Advanced settings for site administrators (page exclusions, default policies) and teachers (toggle visibility per course or specific pages).
- **Default Site Policy**: Administrators can define whether the feature should be enabled or disabled by default for newly configured courses.
- **Highlight to Note**: Students can select text anywhere inside a course page and use a floating action button to save the selection instantly as a quote.
- **Scroll to Text Fragments**: Quote references use browser text fragments (`#:~:text=`), allowing QuickNote to return users to the exact original passage and highlight it visually.
- **Quote and Reflection Separation**: The interface clearly separates the quoted course text from the student's own annotation or reflection.
- **Native Sidebar Drawer**: Notes are managed inside a right-hand sidebar drawer integrated with the Boost user experience, accessible from a floating action button or navigation entry point.
- **Notes Center (view.php):** A dedicated dashboard for students to view all their notes with Global Search, Course Filtering, sorted by last modified date, and equipped with Pagination (configurable display limits) for optimal database performance.
- **Markdown Notes:** Notes are written as raw Markdown and safely rendered by Moodle, with support for headings, bold, italics and lists. Existing notes retain their plain-text format until edited.
- **PDF and Markdown Export:** Users can export their compiled notes directly to PDF or Markdown. PDF exports include attached screenshots where the server image stack supports their format.
- **Auto-save & Core Safety**: Notes are saved automatically via Moodle AJAX services. Includes native Backup & Restore support and automatic deletion of notes when a user is unenrolled.
- **Search and Management**: Search covers note text, page or course titles and tags. Notes can be filtered by course or tag and deleted from the Notes Center.
- **Private Categories:** User-specific Moodle tags are stored in a dedicated QuickNote collection excluded from Moodle's global tag search.
- **Every Moodle Page**: Authorised users receive the existing floating drawer on ordinary course, site front page and administration pages.
- **Page-specific plus Global Notes**: The drawer shows notes for the current page and the user's own notes marked as global. A per-note switch changes that status.
- **Pasted Screenshots**: Focus a note and paste a screenshot with Ctrl+V. Up to ten PNG, JPEG, WebP or GIF images of at most 5 MB each are stored privately with the note.
- **Explicit Permission**: `local/quicknote:use` controls the complete UI, APIs, Notes Center and private image delivery.

## ✅ Prerequisites

- Moodle `4.4+` (Compatible with Moodle < 4.4 down to `4.1` with limitations, see below)
- Boost theme
- Boost child themes are also supported

QuickNote is designed natively for the Moodle Boost interface. Compatibility with non-Boost themes is not the primary target.

## ⚙️ Configuration

This downstream fork uses one system capability instead of course/activity switches.

### Administrator settings

The capability is deliberately not granted to any standard role. Moodle site administrators have it automatically. To enable one additional person without opening QuickNote to everyone:

1. Create a small custom system role that grants only `local/quicknote:use` (plus any unrelated permissions that person actually needs).
2. Assign that role to the selected user in the system context.
3. Keep the role unassigned for everyone else.

The QuickNote settings page still controls drawer position and Notes Center pagination.

The former per-course and per-activity controls are not used by this personal fork because the explicit system capability is the availability boundary.

### Backup and Restore

When backing up a course, QuickNote preserves your course and activity-level settings. **Student notes and reflections are intentionally excluded** from course backups to protect student privacy and ensure personal data is not leaked when courses are restored or shared.

## 📦 Installation

QuickNote can be installed like any standard Moodle local plugin.

### Option 1: Install from ZIP

1. Download the plugin ZIP package.
2. Log in to Moodle as an administrator.
3. Go to `Site administration > Plugins > Install plugins`.
4. Upload the ZIP file.
5. Follow the Moodle installation steps.
6. Complete the upgrade process when prompted.

### Option 2: Install from Git

Clone or copy the plugin into your Moodle local plugins directory:

```bash
git clone https://github.com/Matheu46/moodle-local_quicknote.git /path/to/moodle/local/quicknote
```

Then complete the Moodle upgrade:

1. Log in as an administrator.
2. Go to `Site administration > Notifications`.

Or run the CLI upgrade:

```bash
php admin/cli/upgrade.php
```

## 🧭 Usage

QuickNote is designed to be simple for students from the first interaction.

1. Open any regular Moodle page.
2. Select a meaningful excerpt from the learning material.
3. Click the floating button that appears near the selection.
4. QuickNote saves the selected passage as a quote and opens the sidebar drawer.
5. Add a personal reflection, interpretation, summary, or study note in the annotation field.
6. Continue reading while notes are saved automatically in the background.
7. Use the search field later to find notes quickly.
8. Click `View in text` to return to the original page location and highlight the saved passage.
9. To attach a screenshot, focus the note text area and paste the image with Ctrl+V.
10. Enable `Show on every page` when a note should be global rather than limited to its source page.

## 🧩 Plugin Details

- **Plugin name**: QuickNote
- **Component**: `local_quicknote`
- **Plugin type**: Local plugin
- **Primary interface target**: Moodle Boost right-hand drawer workflow

## 🐛 Bug Reports & Support

If you find a bug or have a feature request, please open an issue on our tracker:
[(https://github.com/Matheu46/moodle-local_quicknote/issues)](#)

Pull requests are welcome!

## 📄 License ##

This program is free software: you can redistribute it and/or modify it under
the terms of the GNU General Public License as published by the Free Software
Foundation, either version 3 of the License, or (at your option) any later
version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY
WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A
PARTICULAR PURPOSE. See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with
this program. If not, see <https://www.gnu.org/licenses/>.
