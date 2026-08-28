<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * QuickNote Notes Center page.
 *
 * @package     local_quicknote
 * @copyright   2026 Matheus Mathias
 * @copyright   2026 Andreas Giesen (downstream changes)
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');

require_login();
$context = context_system::instance();
require_capability('local/quicknote:use', $context);

$coursefilter = optional_param('coursefilter', 0, PARAM_INT);
$tagfilter = optional_param('tagfilter', 0, PARAM_INT);
if (!$tagfilter) {
    // Moodle appends tagid when following the custom URL of the private tag collection.
    $tagfilter = optional_param('tagid', 0, PARAM_INT);
}
$searchterm = optional_param('searchterm', '', PARAM_TEXT);
$export = optional_param('export', '', PARAM_ALPHA);
$page = optional_param('page', 0, PARAM_INT);
$deleteid = optional_param('deleteid', 0, PARAM_INT);
$perpage = get_config('local_quicknote', 'perpage');
if ($perpage === false) {
    $perpage = 12;
}
$perpage = (int) $perpage;

$urlparams = [];
if ($coursefilter) {
    $urlparams['coursefilter'] = $coursefilter;
}
if ($tagfilter) {
    $urlparams['tagfilter'] = $tagfilter;
}
if ($searchterm !== '') {
    $urlparams['searchterm'] = $searchterm;
}
if ($page) {
    $urlparams['page'] = $page;
}
$url = new moodle_url('/local/quicknote/view.php', $urlparams);

if ($deleteid) {
    require_sesskey();
    $deletenote = $DB->get_record('local_quicknote_notes', [
        'id' => $deleteid,
        'userid' => $USER->id,
    ], 'id,userid', MUST_EXIST);
    \local_quicknote\local\screenshot_manager::delete_for_note((int) $deletenote->id);
    \local_quicknote\local\tag_manager::remove_for_note((int) $deletenote->id, (int) $USER->id);
    $DB->delete_records('local_quicknote_notes', ['id' => $deletenote->id, 'userid' => $USER->id]);
    redirect($url);
}

$PAGE->set_url($url);
$PAGE->set_context($context);
$PAGE->set_title(get_string('notescenter', 'local_quicknote'));
$PAGE->set_heading(get_string('notescenter', 'local_quicknote'));
$PAGE->set_pagelayout('standard');

$sqlcourses = "SELECT DISTINCT c.id, c.fullname
                 FROM {course} c
                 JOIN {local_quicknote_notes} qn ON qn.courseid = c.id
                WHERE qn.userid = :userid
             ORDER BY c.fullname ASC";
$usercourses = $DB->get_records_sql($sqlcourses, ['userid' => $USER->id]);
$courses = [];
foreach ($usercourses as $course) {
    $courses[] = [
        'id' => $course->id,
        'fullname' => format_string($course->fullname, true, ['context' => context_course::instance($course->id)]),
        'selected' => (int) $course->id === $coursefilter,
    ];
}

$tagcomponent = \local_quicknote\local\tag_manager::COMPONENT;
$tagitemtype = \local_quicknote\local\tag_manager::ITEMTYPE;
$usertags = [];
if (\local_quicknote\local\tag_manager::is_enabled()) {
    $sqltags = "SELECT DISTINCT t.id, t.name, t.rawname
                  FROM {tag} t
                  JOIN {tag_instance} ti ON ti.tagid = t.id
                  JOIN {local_quicknote_notes} qn ON qn.id = ti.itemid
                 WHERE ti.component = :component
                   AND ti.itemtype = :itemtype
                   AND ti.tiuserid = :tiuserid
                   AND qn.userid = :noteuserid
              ORDER BY t.rawname ASC";
    $tagrecords = $DB->get_records_sql($sqltags, [
        'component' => $tagcomponent,
        'itemtype' => $tagitemtype,
        'tiuserid' => $USER->id,
        'noteuserid' => $USER->id,
    ]);
    foreach ($tagrecords as $tag) {
        $usertags[] = [
            'id' => (int) $tag->id,
            'name' => format_string($tag->rawname ?: $tag->name, true, ['context' => $context]),
            'selected' => (int) $tag->id === $tagfilter,
        ];
    }
}

$hasnotestosearch = $DB->record_exists('local_quicknote_notes', ['userid' => $USER->id]);
$sqlfrom = "FROM {local_quicknote_notes} qn
       LEFT JOIN {course} c ON c.id = qn.courseid
           WHERE qn.userid = :userid";
$params = ['userid' => $USER->id];

if ($coursefilter > 0) {
    $sqlfrom .= " AND qn.courseid = :courseid";
    $params['courseid'] = $coursefilter;
}

if ($tagfilter > 0) {
    $sqlfrom .= " AND EXISTS (
        SELECT 1 FROM {tag_instance} tif
         WHERE tif.itemid = qn.id AND tif.tagid = :tagfilter
           AND tif.component = :tagfiltercomponent AND tif.itemtype = :tagfilteritemtype
           AND tif.tiuserid = :tagfilteruserid
    )";
    $params += [
        'tagfilter' => $tagfilter,
        'tagfiltercomponent' => $tagcomponent,
        'tagfilteritemtype' => $tagitemtype,
        'tagfilteruserid' => $USER->id,
    ];
}

if ($searchterm !== '') {
    $escapedsearch = '%' . $DB->sql_like_escape($searchterm) . '%';
    $contentlike = $DB->sql_like('qn.content', ':searchcontent', false, false);
    $quotelike = $DB->sql_like('qn.quote', ':searchquote', false, false);
    $pagetitlelike = $DB->sql_like('qn.pagetitle', ':searchpagetitle', false, false);
    $courselike = $DB->sql_like('c.fullname', ':searchcourse', false, false);
    $taglike = $DB->sql_like('ts.name', ':searchtagname', false, false);
    $tagrawlike = $DB->sql_like('ts.rawname', ':searchtagrawname', false, false);
    $sqlfrom .= " AND (
        {$contentlike} OR {$quotelike} OR {$pagetitlelike} OR {$courselike}
        OR EXISTS (
            SELECT 1 FROM {tag_instance} tis JOIN {tag} ts ON ts.id = tis.tagid
             WHERE tis.itemid = qn.id AND tis.component = :searchtagcomponent
               AND tis.itemtype = :searchtagitemtype AND tis.tiuserid = :searchtaguserid
               AND ({$taglike} OR {$tagrawlike})
        )
    )";
    $params += [
        'searchcontent' => $escapedsearch,
        'searchquote' => $escapedsearch,
        'searchpagetitle' => $escapedsearch,
        'searchcourse' => $escapedsearch,
        'searchtagname' => $escapedsearch,
        'searchtagrawname' => $escapedsearch,
        'searchtagcomponent' => $tagcomponent,
        'searchtagitemtype' => $tagitemtype,
        'searchtaguserid' => $USER->id,
    ];
}

$totalcount = $DB->count_records_sql("SELECT COUNT(qn.id) " . $sqlfrom, $params);
$sql = "SELECT qn.id, qn.userid, qn.content, qn.contentformat, qn.url, qn.quote, qn.quoteurl,
               qn.timemodified, qn.pagehash, qn.pagetitle, qn.isglobal, qn.courseid,
               c.fullname AS coursefullname
          " . $sqlfrom . "
      ORDER BY qn.timemodified DESC, qn.id DESC";

if ($export === 'pdf' || $export === 'md' || $perpage === 0) {
    $noterecords = $DB->get_records_sql($sql, $params);
} else {
    $noterecords = $DB->get_records_sql($sql, $params, $page * $perpage, $perpage);
}
$notetags = \local_quicknote\local\tag_manager::get_for_notes(array_keys($noterecords), (int) $USER->id);

$recordlabel = static function(stdClass $record): string {
    if (!empty($record->pagetitle)) {
        return format_string($record->pagetitle, true, ['context' => context_system::instance()]);
    }
    if (!empty($record->courseid) && !empty($record->coursefullname)) {
        return format_string($record->coursefullname, true, ['context' => context_course::instance($record->courseid)]);
    }
    return get_string('unknownpage', 'local_quicknote');
};
$recordformat = static function(stdClass $record): int {
    $format = isset($record->contentformat) ? (int) $record->contentformat : FORMAT_PLAIN;
    return in_array($format, [(int) FORMAT_PLAIN, (int) FORMAT_MARKDOWN], true) ? $format : (int) FORMAT_PLAIN;
};
$recordhtml = static function(stdClass $record) use ($recordformat, $context): string {
    return format_text((string) $record->content, $recordformat($record), [
        'context' => $context,
        'filter' => false,
    ]);
};

if ($export === 'pdf') {
    require_once($CFG->libdir . '/pdflib.php');
    $pdf = new \pdf();
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(true);
    $pdf->AddPage();
    $pdf->SetFont('helvetica', '', 12);
    $pdf->writeHTML('<h2>' . s(get_string('notescenter', 'local_quicknote')) . '</h2>', true, false, true, false, '');

    if (empty($noterecords)) {
        $pdf->writeHTML('<p>' . s(get_string('note:empty', 'local_quicknote')) . '</p>', true, false, true, false, '');
    } else {
        $currentgroup = null;
        foreach ($noterecords as $record) {
            $files = \local_quicknote\local\screenshot_manager::get_stored_files_for_note((int) $record->id);
            if (trim((string) $record->content) === '' && trim((string) $record->quote) === '' && !$files) {
                continue;
            }
            $html = '';
            $group = !empty($record->isglobal) ? 'global' : (string) $record->pagehash;
            if ($currentgroup !== $group) {
                $label = !empty($record->isglobal)
                    ? get_string('note:globalbadge', 'local_quicknote') : $recordlabel($record);
                $html .= '<h3 style="color:#0056b3;border-bottom:1px solid #eee;">' . s($label) . '</h3>';
                $currentgroup = $group;
            }
            $timeupdated = userdate($record->timemodified, get_string('strftimedatetimeshort', 'langconfig'));
            $html .= '<p style="text-align:right"><small><i>' . s($timeupdated) . '</i></small></p>';
            $tags = $notetags[(int) $record->id] ?? [];
            if ($tags) {
                $names = array_map(static fn(array $tag): string => $tag['name'], $tags);
                $html .= '<p><small><b>' . s(get_string('tags', 'local_quicknote')) . ':</b> '
                    . s(implode(', ', $names)) . '</small></p>';
            }
            if (!empty($record->quote)) {
                $quote = format_text($record->quote, FORMAT_PLAIN, ['context' => $context, 'filter' => false]);
                $html .= '<blockquote style="color:#555"><i>' . $quote . '</i></blockquote>';
            }
            if (!empty($record->url)) {
                $html .= '<p><small><a href="' . s(clean_param($record->url, PARAM_URL)) . '">'
                    . s(get_string('note:viewintext', 'local_quicknote')) . '</a></small></p>';
            }
            if (trim((string) $record->content) !== '') {
                $html .= '<div>' . $recordhtml($record) . '</div>';
            }
            $pdf->writeHTML($html, true, false, true, false, '');

            foreach ($files as $file) {
                $content = $file->get_content();
                $imageinfo = @getimagesizefromstring($content);
                if (!$imageinfo || empty($imageinfo[0]) || empty($imageinfo[1])) {
                    continue;
                }
                $width = min(160.0, (float) $imageinfo[0] * 0.264583);
                $height = $width * ((float) $imageinfo[1] / (float) $imageinfo[0]);
                if ($height > 90.0) {
                    $width *= 90.0 / $height;
                    $height = 90.0;
                }
                if ($pdf->GetY() + $height + 12 > $pdf->getPageHeight() - 15) {
                    $pdf->AddPage();
                }
                $pdf->writeHTML('<p><small>' . s(get_string('screenshot:attachment', 'local_quicknote')) . ': '
                    . s($file->get_filename()) . '</small></p>', true, false, true, false, '');
                try {
                    $pdf->Image('@' . $content, '', '', $width, $height, '', '', 'N', true, 150, '', false, false, 0,
                        false, false, true);
                } catch (Throwable $exception) {
                    $pdf->writeHTML('<p><small>' . s($file->get_filename()) . '</small></p>', true, false, true, false, '');
                }
                unset($content);
            }
            $pdf->writeHTML('<hr style="color:#eee">', true, false, true, false, '');
        }
    }
    $pdf->Output('my_quicknotes.pdf', 'D');
    die();
}

if ($export === 'md') {
    $md = '# ' . get_string('notescenter', 'local_quicknote') . "\n\n";
    if (empty($noterecords)) {
        $md .= get_string('note:empty', 'local_quicknote') . "\n";
    } else {
        $currentgroup = null;
        foreach ($noterecords as $record) {
            $screenshots = \local_quicknote\local\screenshot_manager::get_for_note((int) $record->id);
            if (trim((string) $record->content) === '' && trim((string) $record->quote) === '' && !$screenshots) {
                continue;
            }
            $group = !empty($record->isglobal) ? 'global' : (string) $record->pagehash;
            if ($currentgroup !== $group) {
                $label = !empty($record->isglobal)
                    ? get_string('note:globalbadge', 'local_quicknote') : $recordlabel($record);
                $md .= '## ' . $label . "\n\n";
                $currentgroup = $group;
            }
            $md .= '**' . userdate($record->timemodified, get_string('strftimedatetimeshort', 'langconfig')) . "**\n\n";
            $tags = $notetags[(int) $record->id] ?? [];
            if ($tags) {
                $md .= '**' . get_string('tags', 'local_quicknote') . ':** '
                    . implode(', ', array_map(static fn(array $tag): string => $tag['name'], $tags)) . "\n\n";
            }
            if (!empty($record->quote)) {
                $quote = html_entity_decode(strip_tags(format_text(
                    $record->quote,
                    FORMAT_PLAIN,
                    ['context' => $context, 'filter' => false]
                )), ENT_QUOTES, 'UTF-8');
                $md .= '> ' . str_replace("\n", "\n> ", $quote) . "\n\n";
            }
            if (!empty($record->url)) {
                $md .= '[_' . get_string('note:viewintext', 'local_quicknote') . '_]('
                    . clean_param($record->url, PARAM_URL) . ")\n\n";
            }
            $md .= (string) $record->content . "\n\n";
            foreach ($screenshots as $screenshot) {
                $md .= '![' . $screenshot['filename'] . '](' . $screenshot['url'] . ")\n\n";
            }
            $md .= "---\n\n";
        }
    }
    header('Content-Type: text/markdown; charset=utf-8');
    header('Content-Disposition: attachment; filename="my_quicknotes.md"');
    echo $md;
    die();
}

$notes = [];
foreach ($noterecords as $record) {
    $screenshots = \local_quicknote\local\screenshot_manager::get_for_note((int) $record->id);
    if (trim((string) $record->content) === '' && trim((string) $record->quote) === '' && !$screenshots) {
        continue;
    }
    $sourceurl = clean_param((string) $record->url, PARAM_URL);
    $quoteurl = clean_param((string) $record->quoteurl, PARAM_URL);
    $actionurl = $quoteurl ?: $sourceurl;
    $notes[] = [
        'id' => (int) $record->id,
        'coursefullname' => $recordlabel($record),
        'isglobal' => !empty($record->isglobal),
        'globalbadge' => get_string('note:globalbadge', 'local_quicknote'),
        'contenthtml' => $recordhtml($record),
        'timeupdated' => userdate($record->timemodified, get_string('strftimedatetimeshort', 'langconfig')),
        'actionurl' => $actionurl ? (new moodle_url($actionurl))->out(false) : null,
        'quote' => !empty($record->quote) ? $record->quote : null,
        'tags' => $notetags[(int) $record->id] ?? [],
        'hastags' => !empty($notetags[(int) $record->id]),
        'screenshots' => $screenshots,
        'hasscreenshots' => !empty($screenshots),
    ];
}

if ($perpage > 0) {
    $pagingbarhtml = $OUTPUT->render(new paging_bar($totalcount, $page, $perpage, $url));
} else {
    $pagingbarhtml = '';
}

$exportparams = $urlparams;
unset($exportparams['page']);
$mdurl = new moodle_url('/local/quicknote/view.php', $exportparams + ['export' => 'md']);
$pdfurl = new moodle_url('/local/quicknote/view.php', $exportparams + ['export' => 'pdf']);
$templatecontext = [
    'pagingbar' => $pagingbarhtml,
    'filterbycourse' => get_string('filterbycourse', 'local_quicknote'),
    'filterbytag' => get_string('filterbytag', 'local_quicknote'),
    'allcourses' => get_string('allcourses', 'local_quicknote'),
    'alltags' => get_string('alltags', 'local_quicknote'),
    'nonotesfound' => get_string('note:empty', 'local_quicknote'),
    'noresultstext' => get_string('search:noresultstext', 'local_quicknote'),
    'searchnotes' => get_string('search:placeholder', 'local_quicknote'),
    'search' => get_string('search', 'local_quicknote'),
    'exportpdf' => get_string('exportpdf', 'local_quicknote'),
    'exportmd' => get_string('exportmd', 'local_quicknote'),
    'hasnotestosearch' => $hasnotestosearch,
    'hasnotes' => !empty($notes),
    'hastags' => !empty($usertags),
    'coursefilter' => $coursefilter,
    'tagfilter' => $tagfilter,
    'page' => $page,
    'searchterm' => $searchterm,
    'courses' => $courses,
    'usertags' => $usertags,
    'notes' => $notes,
    'mdurl' => $mdurl->out(false),
    'pdfurl' => $pdfurl->out(false),
    'sesskey' => sesskey(),
    'deleteconfirm' => get_string('note:delete_confirm', 'local_quicknote'),
];

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_quicknote/view', $templatecontext);
echo $OUTPUT->footer();
