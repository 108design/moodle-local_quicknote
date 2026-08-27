# QuickNote — 108design downstream fork

QuickNote is a private note drawer for Moodle. This 108design fork keeps the original component name, `local_quicknote`, so an existing upstream installation upgrades in place without losing its notes.

This repository is forked from [Matheu46/moodle-local_quicknote](https://github.com/Matheu46/moodle-local_quicknote). The original authorship and GNU GPL v3-or-later licence are retained. Changes introduced by this fork are authored by Andreas Giesen.

## What this fork adds

- The floating QuickNote drawer is available on ordinary authenticated Moodle pages, including courses, the site front page and administration pages.
- Notes are private to their owner and bound to a stable identity for the current page.
- A per-note **Global** switch makes a private note visible in the owner's drawer on every supported page.
- Screenshots can be pasted into a focused note with <kbd>Ctrl</kbd>+<kbd>V</kbd> and are stored privately through Moodle's File API.
- Access is controlled by the system capability `local/quicknote:use`.
- English and German interfaces are included.

The existing Notes Center, search, PDF/Markdown export, quote capture and text-fragment links remain available.

## Requirements

- Moodle 4.2 or later
- PHP compatible with the selected Moodle release
- Boost or a Boost-derived theme is recommended

## Installation

Install the release ZIP as a normal Moodle local plugin, or clone the repository into `local/quicknote`:

```bash
git clone https://github.com/108design/moodle-local_quicknote.git /path/to/moodle/local/quicknote
```

Then complete the Moodle upgrade as the web-site owner:

```bash
php admin/cli/upgrade.php --non-interactive
php admin/cli/purge_caches.php
```

## Upgrading from the original plugin

Do not install this fork beside the original plugin. It deliberately uses the same component and directory:

- component: `local_quicknote`
- directory: `local/quicknote`

Replace the existing source with this fork and run the normal Moodle upgrade. Version `0.10.0-108design.1` migrates existing URL-bound notes to the page-specific model.

Back up the existing plugin directory and database before changing a production installation.

## Permission model

Moodle site administrators have `local/quicknote:use` implicitly. The capability is intentionally not granted to any standard role.

To enable QuickNote for another person, create a small custom role that grants `local/quicknote:use` and assign that role in the system context. Notes and screenshots remain private to the owning user; the plugin does not provide an administrator-reading surface.

## Page-specific and global notes

The drawer shows:

- notes belonging to the current canonical page; and
- the current user's own notes marked as global.

Page identity ignores fragments and volatile Moodle arguments such as `sesskey` and `_qf__`. Turning the Global switch off binds the note to the page where the switch was changed.

## Screenshot limits

- PNG, JPEG, WebP and GIF
- maximum 5 MiB per file
- maximum 10 screenshots per note
- maximum 40 megapixels per image

Private file delivery checks login, `local/quicknote:use` and note ownership.

## Privacy and exports

Deleting a note or user data removes associated screenshots. Moodle's Privacy API includes the screenshot file area. PDF and Markdown exports list attached screenshot filenames but do not currently embed the images.

## Development

The source supports Moodle's standard plugin CI workflow. After changing `amd/src/*.js`, rebuild the corresponding files in `amd/build/` and commit both source and generated output.

Bug reports for this fork belong in the [108design issue tracker](https://github.com/108design/moodle-local_quicknote/issues). Issues concerning the unmodified upstream plugin should be reported to the [original project](https://github.com/Matheu46/moodle-local_quicknote/issues).

## Licence and authorship

QuickNote is free software under the GNU General Public License, version 3 or later. See [LICENSE.md](LICENSE.md).

- Original QuickNote: Matheus Mathias and upstream contributors
- 108design downstream changes: Andreas Giesen

The fork preserves upstream notices and attributes Andreas Giesen only for downstream changes.
