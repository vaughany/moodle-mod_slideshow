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

/**
 * This page lists all the instances of slideshow in a particular course.
 */
global $DB;
require_once('../../config.php');
require_once('lib.php');

$id = required_param('id', PARAM_INT);

if (! $course = $DB->get_record('course', array('id' => $id))) {
    error('Course ID is incorrect');
}

require_login($course->id);

add_to_log($course->id, 'slideshow', 'view all', 'index.php?id='.$course->id, '');

// Get all required strings.
$strslideshows = get_string("modulenameplural", "slideshow");
$strslideshow  = get_string("modulename", "slideshow");

$PAGE->set_url('/mod/slideshow/index.php', array('id' => $id));
$PAGE->navbar->add($strslideshows);

// Print the header.
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('slideshowsfound', 'slideshow', $course->shortname));

// Get all the appropriate data.
$slideshows = get_all_instances_in_course('slideshow', $course);

// Print the list of instances (your module will probably extend this).
$timenow = time();
$strname  = get_string("name");
$strweek  = get_string("week");
$strtopic  = get_string("topic");
$table = new html_table();

if ($course->format == "weeks") {
    $table->head  = array ($strweek, $strname);
    $table->align = array ("CENTER", "LEFT");
} else if ($course->format == "topics") {
    $table->head  = array ($strtopic, $strname);
    $table->align = array ("CENTER", "LEFT", "LEFT", "LEFT");
} else {
    $table->head  = array ($strname);
    $table->align = array ("LEFT", "LEFT", "LEFT");
}

foreach ($slideshows as $slideshow) {
    if (!$slideshow->visible) {
        // Show dimmed if the mod is hidden.
        $link = '<a class="dimmed" href="view.php?id='.$slideshow->coursemodule.'">'.$slideshow->name.'</a>';
    } else {
        // Show normal if the mod is visible.
        $link = '<a href="view.php?id='.$slideshow->coursemodule.'">'.$slideshow->name.'</a>';
    }

    if ($course->format == "weeks" or $course->format == "topics") {
        $table->data[] = array ($slideshow->section, $link);
    } else {
        $table->data[] = array ($link);
    }
}

echo "<br />";

echo html_writer::table($table);

// Finish the page.
echo $OUTPUT->footer($course);
