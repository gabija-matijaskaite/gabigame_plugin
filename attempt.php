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
 * This page prints a particular attempt of game
 *
 * @package mod_game
 * @copyright  2022 Gabija Matijaškaitė <gabija.matijaskaite@mif.stud.vu.lt>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 **/
require_once(dirname(__FILE__).'/../../config.php');
require_once( "lib.php");
require_once("functions.php");

$action  = optional_param('action', "", PARAM_ALPHANUM);  // Is the param action.

gabigame_show_header( $id, $gabigame, $course, $context, $cm, $ggid, $uid);
gabigame_do_attempt( $gabigame, $action, $course, $context, $cm, $ggid, $uid);

/**
 * Do the required checks and print header.
 *
 * @param int $id
 * @param stdClass $game
 * @param stdClass $course
 * @param stdClass $context
 * @param stdClass $cm
 */
function gabigame_show_header( &$id, &$gabigame, &$course, &$context, &$cm, &$ggid, &$uid) {
    global $DB, $USER, $PAGE, $OUTPUT;

    $id = optional_param('id', 0, PARAM_INT); // It represents Course Module ID.
    $q = optional_param('q',  0, PARAM_INT);  // It represents game id.

    if ($id) {
        if (! $cm = get_coursemodule_from_id('gabigame', $id)) {
            print_error('invalidcoursemodule');
        }
        if (! $course = $DB->get_record('course', array('id' => $cm->course))) {
            print_error('coursemisconf');
        }
        if (! $gabigame = $DB->get_record('gabigame', array('id' => $cm->instance))) {
            print_error('invalidcoursemodule');
        }
    } else {
        if (! $gabigame = $DB->get_record('gabigame', array('id' => $q))) {
            print_error('invalidgabigameid', 'gabigame');
        }
        if (! $course = $DB->get_record('course', array('id' => $gabigame->course))) {
            print_error('invalidcourseid');
        }
        if (! $cm = get_coursemodule_from_instance('gabigame', $gabigame->id, $course->id)) {
            print_error('invalidcoursemodule');
        }
    }

    // Check login and get context.
    require_login($course->id, false, $cm);
    $context = gabigame_get_context_module_instance( $cm->id);
    require_capability('mod/gabigame:view', $context);

    // Cache some other capabilites we use several times.
    $canattempt = has_capability('mod/gabigame:attempt', $context);
    $canreviewmine = has_capability('mod/gabigame:reviewmyattempts', $context);

    // Create an object to manage all the other (non-roles) access rules.
    $timenow = time();

    // Set parameters for gabigame_attempts table
    $uid = $USER->id;
    $ggid = $gabigame->id;

    // Initialize $PAGE, compute blocks.
    $PAGE->set_url('/mod/gabigame/view.php', array('id' => $cm->id));

    $edit = optional_param('edit', -1, PARAM_BOOL);
    if ($edit != -1 && $PAGE->user_allowed_editing()) {
        $USER->editing = $edit;
    }

    $title = $course->shortname . ': ' . format_string($gabigame->name);

    if ($PAGE->user_allowed_editing() && !empty($CFG->showblocksonmodpages)) {
        $buttons = '<table><tr><td><form method="get" action="view.php"><div>'.
                '<input type="hidden" name="id" value="'.$cm->id.'" />'.
                '<input type="hidden" name="edit" value="'.($PAGE->user_is_editing() ? 'off' : 'on').'" />'.
                '<input type="submit" value="'.get_string($PAGE->user_is_editing() ? 'blockseditoff' : 'blocksediton').
                '" /></div></form></td></tr></table>';
        $PAGE->set_button($buttons);
    }

    $PAGE->set_title($title);
    $PAGE->set_heading($course->fullname);

    echo $OUTPUT->header();
}

function gabigame_do_attempt( $gabigame, $action, $course, $context, $cm, $ggid, $uid){
    global $OUTPUT;

    //Game here
    echo "<iframe width=800 height=600 src='game/index.html?uid=$uid&ggid=$ggid' name='targetframe' allowTransparency='true' scrolling='yes' frameborder='100' ></iframe>";

    // Finish the page.
    echo $OUTPUT->footer();   
}

?>
  