<?php
// This file is part of Moodle - http://moodle.org/
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
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * @package moodlecore
 * @subpackage slideshow
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

function slideshow_migrate_20() {
    global $DB;
    $slideshows = $DB->get_records('slideshow');
    $browser = get_file_browser();
    $fs = get_file_storage();

    foreach ($slideshows as $slideshow) {

        // Looking for course files into file datatable.
        $coursecontext = get_context_instance(CONTEXT_COURSE, $slideshow->course);
        $cm = get_coursemodule_from_instance('slideshow', $slideshow->id, $slideshow->course, false, MUST_EXIST);
        $filesrecords = $fs->get_directory_files($coursecontext->id, 'course', 'legacy', 0, empty($slideshow->location) ? '/' : '/'.$slideshow->location.'/');
        foreach ($filesrecords as $filesrecord) {

            // Filter files only images.
            if (preg_match("/\.jpe?g$/i", $filesrecord->get_filename()) || preg_match("/\.gif$/i", $filesrecord->get_filename())
                || preg_match("/\.png$/i", $filesrecord->get_filename())) {

                $newrecordfiles = new stdClass();
                $newrecordfiles->contextid      = get_context_instance(CONTEXT_MODULE, $cm->id)->id;
                $newrecordfiles->component      = 'mod_slideshow';
                $newrecordfiles->filearea       = 'content';
                $newrecordfiles->itemid         = 0;
                $newrecordfiles->filepath       = '/';
                $newrecordfiles->filename       = $filesrecord->get_filename();
                $newrecordfiles->userid         = $filesrecord->get_userid();
                $newrecordfiles->contenthash    = $filesrecord->get_contenthash();
                $newrecordfiles->filesize       = $filesrecord->get_filesize();
                $newrecordfiles->mimetype       = $filesrecord->get_mimetype();
                $newrecordfiles->source         = $filesrecord->get_source();
                $newrecordfiles->author         = $filesrecord->get_author();
                $newrecordfiles->license        = $filesrecord->get_license();
                $newrecordfiles->pathnamehash   = $fs->get_pathname_hash($newrecordfiles->contextid, $newrecordfiles->component,
                    $newrecordfiles->filearea, $newrecordfiles->itemid, $newrecordfiles->filepath, $newrecordfiles->filename);
                $newrecordfiles->timecreated    = $slideshow->timemodified;
                $newrecordfiles->timemodified   = $slideshow->timemodified;
                $newrecordfiles->id             = $DB->insert_record('files', $newrecordfiles);
                if ($newrecordfiles) {
                    $createddirectory = $fs->create_directory($newrecordfiles->contextid, $newrecordfiles->component,
                        $newrecordfiles->filearea, $newrecordfiles->itemid, $newrecordfiles->filepath, $newrecordfiles->userid);
                }
            }
        }

        // Update slideshow record.
        $slideshow->location = 0;
        $DB->update_record('slideshow', $slideshow);
        $captionrecords = $DB->get_records('slideshow_captions', array('slideshow' => $cm->id));
        foreach ($captionrecords as $captionrecord) {
            $captionrecord->slideshow = $slideshow->id;
            $DB->update_record('slideshow_captions', $captionrecord);
        }
    }
}
