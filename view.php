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
 * Prints a particular instance of gabigame
 *
 * You can have a rather longer description of the file as well,
 * if you like, and it can span multiple lines.
 *
 * @package    mod_gabigame
 * @copyright  2022 Gabija Matijaškaitė <gabija.matijaskaite@mif.stud.vu.lt>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once($CFG->libdir.'/gradelib.php');
require_once(dirname(__FILE__).'/functions.php');

$id = optional_param('id', 0, PARAM_INT); // Either course_module ID, or ...
$n  = optional_param('q', 0, PARAM_INT);  // ...gabigame instance ID - it should be named as the first character of the module.

if ($id) {
    $cm         = get_coursemodule_from_id('gabigame', $id, 0, false, MUST_EXIST);
    $course     = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
    $gabigame  = $DB->get_record('gabigame', array('id' => $cm->instance), '*', MUST_EXIST);
} else if ($n) {
    $gabigame  = $DB->get_record('gabigame', array('id' => $n), '*', MUST_EXIST);
    $course     = $DB->get_record('course', array('id' => $gabigame->course), '*', MUST_EXIST);
    $cm         = get_coursemodule_from_instance('gabigame', $gabigame->id, $course->id, false, MUST_EXIST);
} else {
    throw new moodle_exception('invalidcmorid', 'gabigame');
}

// Check login and get context.
require_login($course->id, false, $cm);
$context = gabigame_get_context_module_instance( $cm->id);
require_capability('mod/gabigame:view', $context);

$timenow = time();

// Cache some other capabilites we use several times.
$canattempt = true;

$strtimeopenclose = '';
if ($timenow < $gabigame->timeopen) {
    $canattempt = false;
    $strtimeopenclose = get_string('gamenotavailable', 'gabigame', userdate($gabigame->timeopen));
} else if ($gabigame->timeclose && $timenow > $gabigame->timeclose) {
    $strtimeopenclose = get_string("gameclosed", "gabigame", userdate($gabigame->timeclose));
    $canattempt = false;
} else {
    if ($gabigame->timeopen) {
        $strtimeopenclose = get_string('gameopenedon', 'gabigame', userdate($gabigame->timeopen));
    }
    if ($gabigame->timeclose) {
        $strtimeopenclose = get_string('gamecloseson', 'gabigame', userdate($gabigame->timeclose));
    }
}

if (has_capability('mod/gabigame:manage', $context)) {
    $canattempt = true;
}

// Print the page header.
$PAGE->set_url('/mod/gabigame/view.php', array('id' => $cm->id));
$PAGE->set_title(format_string($gabigame->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);
$PAGE->set_focuscontrol('mod_gabigame_game');
$renderer = $PAGE->get_renderer('mod_gabigame');

// Get this user's finished attempts.
$attempts = gabigame_get_user_attempts($gabigame->id, $USER->id);

// Get this user's unfinished attempts.
$unfinished = false;
if ($unfinishedattempt = gabigame_get_user_attempt_unfinished($gabigame->id, $USER->id)) {
    $unfinished = true;
}

$numattempts = count($attempts);
$gradecolumn = $gabigame->grade > 0;

// Output starts here.
echo $OUTPUT->header();

// Display information about this game.
if ($gabigame->intro) {
    echo $OUTPUT->box(format_module_intro('gabigame', $gabigame, $cm->id), 'generalbox mod_introbox', 'gabigameintro');
}

// Output header and directions.
echo $OUTPUT->heading_with_help(get_string('modulename', 'mod_gabigame'), 'howtoplay', 'mod_gabigame');

// Display information about grading method.
echo get_string('gradingmethod', 'quiz', gabigame_get_grading_option_name($gabigame->grademethod));
echo '<br>';

// Show number of attempts summary to those who can view reports.
if (has_capability('mod/gabigame:viewreports', $context)) {
    if ($strattemptnum = gabigame_get_user_attempts($gabigame->id, $USER->id)) {
        echo get_string( 'attempts', 'gabigame').': '.count( $strattemptnum);
        if ($gabigame->maxattempts) {
            echo ' ('.get_string( 'max', 'quiz').': '.$gabigame->maxattempts.')';
        }
    }
}

// Work out the final grade, checking whether it was overridden in the gradebook.
$mygrade = gabigame_get_best_grade($gabigame, $USER->id);
$mygradeoverridden = false;
$gradebookfeedback = '';

$gradinginfo = grade_get_grades($course->id, 'mod', 'gabigame', $gabigame->id, $USER->id);
if (!empty($gradinginfo->items)) {
    $item = $gradinginfo->items[0];
    if (isset($item->grades[$USER->id])) {
        $grade = $item->grades[$USER->id];

        if ($grade->overridden) {
            $mygrade = $grade->grade + 0; // Convert to number.
            $mygradeoverridden = true;
        }
        if (!empty($grade->str_feedback)) {
            $gradebookfeedback = $grade->str_feedback;
        }
    }
}

// Determine if we should be showing a start/continue attempt button or a button to go back to the course page.
echo $OUTPUT->box_start('gameattempt');
$buttontext = ''; // This will be set something if as start/continue attempt button should appear.

if ($unfinished) {        
        if ($canattempt) {
            $buttontext = get_string('continueattemptgame', 'gabigame');
        }
} else {
    if ($canattempt) {
        if (count($attempts) == 0) {
            $buttontext = get_string('attemptgamenow', 'gabigame');
        } else {
            $buttontext = get_string('reattemptgame', 'gabigame');
        }
    }
}

// Now actually print the appropriate button.
if ($buttontext) {
    global $OUTPUT;

    $strconfirmstartattempt = '';

    // Show the start button, in a div that is initially hidden.
    echo '<div id="gamestartbuttondiv">';
    $url = new moodle_url($CFG->wwwroot.'/mod/gabigame/attempt.php', array('id' => $id));
    $button = new single_button($url, $buttontext);
    echo $OUTPUT->render($button);
    echo "</div>\n";
} else {
    echo $OUTPUT->continue_button($CFG->wwwroot . '/course/view.php?id=' . $course->id);
}

echo $OUTPUT->box_end();

if($numattempts > 0 || $unfinished){
    echo $OUTPUT->heading('___________________________________');
    echo $OUTPUT->heading(get_string('summaryofattempts', 'quiz'));
}

// Print table with existing attempts.
foreach($attempts as $attempt){
    $attempt->id = $attempt->worldid*10+$attempt->levelid;
}

$userid = $USER->id;

for ($x = 11; $x <= 14; $x++) { gabigame_create_attempt_table($OUTPUT, $gabigame, $attempts, $course, $CFG, $x, $userid, $canattempt, $mygrade, $id); }
for ($x = 21; $x <= 24; $x++) { gabigame_create_attempt_table($OUTPUT, $gabigame, $attempts, $course, $CFG, $x, $userid, $canattempt, $mygrade, $id); }
for ($x = 31; $x <= 32; $x++) { gabigame_create_attempt_table($OUTPUT, $gabigame, $attempts, $course, $CFG, $x, $userid, $canattempt, $mygrade, $id); }
gabigame_create_attempt_table($OUTPUT, $gabigame, $attempts, $course, $CFG, 41, $userid, $canattempt, $mygrade, $id);

// Print information about the student's best score for this game if possible.
if ($numattempts && $gradecolumn && !is_null($mygrade)) {
    $resultinfo = '';

    $a = new stdClass;
    $a->grade = gabigame_format_grade($gabigame, $mygrade);
    $a->maxgrade = gabigame_format_grade($gabigame, $gabigame->grade);
    $a = get_string('outofshort', 'quiz', $a);
    $resultinfo .= $OUTPUT->heading(get_string('yourfinalgradeis', 'gabigame', $a), 2, 'main');

    if ($mygradeoverridden) {
        $resultinfo .= '<p class="overriddennotice">'.get_string('overriddennotice', 'grades')."</p>\n";
    }

    if ($gradebookfeedback) {
        $resultinfo .= $OUTPUT->heading(get_string('comment', 'gabigame'), 3, 'main');
        $resultinfo .= '<p class="gameteacherfeedback">'.$gradebookfeedback."</p>\n";
    }

    if ($resultinfo) {
        echo $OUTPUT->box($resultinfo, 'generalbox', 'feedback');
    }
}

// Finish the page.
echo $OUTPUT->footer();

function gabigame_create_attempt_table($OUTPUT, $gabigame, $attempts, $course, $CFG, $code, $userid, $canattempt, $mygrade, $id){
    
    $newattempts = array();
    foreach($attempts as $attempt){
        if($attempt->id == $code){
            $newattempts[] = $attempt;
        }        
    }
    
    $unfinished = false;
    if ($unfinishedattempt = gabigame_get_user_attempt_unfinished($gabigame->id, $userid, $code)) {
        $newattempts[] = $unfinishedattempt;
        $unfinished = true;
    }

    $numattempts = count($newattempts);
    $gradecolumn = $gabigame->grade > 0;

    if($numattempts){

        $world = substr($code, 0, 1);
        $level = substr($code, 1, 2);
        $color = gabigame_set_header_color($world);
        echo $OUTPUT->heading('___________________________________');
        echo $OUTPUT->heading('<span style="color:#'.$color.';font-weight:bold">'.get_string('world', 'gabigame').' '.$world.'</span> - '.get_string('level', 'gabigame').' <span style="font-weight:bold">'.$level."</span>");

        // Prepare table header.
        $table = new html_table();
        $table->attributes['class'] = 'generaltable gameattemptsummary';
        $table->head = array();
        $table->align = array();
        $table->size = array();
        $table->head[] = get_string('attempt', 'gabigame');
        $table->align[] = 'center';
        $table->size[] = '';
        $table->head[] = get_string('timecompleted', 'gabigame');
        $table->align[] = 'left';
        $table->size[] = '';

        if ($gradecolumn) {
            $table->head[] = get_string('grade', 'gabigame'). ' / ' .gabigame_format_grade($gabigame, $gabigame->grade);
            $table->align[] = 'center';
            $table->size[] = '';
        }

        $table->head[] = get_string('timetaken', 'gabigame');
        $table->align[] = 'left';
        $table->size[] = '';

        // One row for each attempt.
        foreach ($newattempts as $attempt) {
            $row = array();

            // Add the attempt number, making it a link, if appropriate.
            if ($attempt->preview) {
                $row[] = get_string('preview', 'gabigame');
            } else {
                $row[] = $attempt->attempt;
            }

            // Prepare strings for time taken and date completed.
            $timetaken = '';
            $datecompleted = '';
            if ($attempt->timefinish > 0) {
                // Attempt has finished.
                $timetaken = format_time($attempt->timefinish - $attempt->timestart);
                $datecompleted = userdate($attempt->timefinish);
            } else {
                // The a is still in progress.
                $timetaken = format_time(time() - $attempt->timestart);
                $datecompleted = '';
            }
            $row[] = $datecompleted;

            $attemptgrade = gabigame_score_to_grade($attempt->score, $gabigame);

            if ($gradecolumn) {
                $formattedgrade = gabigame_format_grade($gabigame, $attemptgrade);
                // Highlight the highest grade if appropriate.
                if (!$attempt->preview && $numattempts > 1 && !is_null($mygrade) && 
                    $attemptgrade == $mygrade /*&& $gabigame->grademethod == QUIZ_GRADEHIGHEST*/) {
                    $table->rowclasses[$attempt->attempt] = 'bestrow';
                }

                $row[] = $formattedgrade;
            }

            $row[] = $timetaken;

            if ($attempt->preview) {
                $table->data['preview'] = $row;
            } else {
                $table->data[$attempt->attempt] = $row;
            }
        } 
        // End of loop over attempts.
        echo html_writer::table($table);
    }
}

function gabigame_set_header_color($world){
    switch($world){
        case 1:
            return "360";
        case 2:
            return "C30";
        case 3:
            return "069";
        case 4:
            return "606";
        default:
            return "000";
    }
}
?>
