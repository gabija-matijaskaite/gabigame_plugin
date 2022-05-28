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
 * English strings for gabigame
 *
 * You can have a rather longer description of the file as well,
 * if you like, and it can span multiple lines.
 *
 * @package    mod_gabigame
 * @copyright  2022 Gabija Matijaškaitė <gabija.matijaskaite@mif.stud.vu.lt>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// Main
$string['pluginname'] = 'Gabi Game';
$string['modulename'] = 'Aviation with code';
$string['modulenameplural'] = 'Gabi Game';
$string['pluginadministration'] = 'Gabi Game administration';

$string['modulename_help'] = 'Educational game integration to moodle.';
$string['gabigamename_help'] = 'Educational game integration to moodle.';

$string['howtoplay'] = 'Aviation with code game';
$string['howtoplay_help'] = '"Aviation with Code" is a coding game that tests your algorithm skills. To understand the mechanics of the game, please read the in-game tutorial. For each level you complete, a score is calculated based on the number of moves and the number of unsuccessful attempts. The final score is the average of the sum of all 11 levels.';

// File mod_form.php
$string[ 'attemptfirst'] = 'First attempt';
$string[ 'attemptlast'] = 'Last attempt';
$string[ 'gradeaverage'] = 'Average grade';
$string[ 'gradehighest'] = 'Highest grade';
$string[ 'grademethod'] = 'Grading method';

// File view.php
$string['attempts'] = 'Completed attempts';
$string['attempt'] = 'Attempt';
$string['timecompleted'] = 'Completed';
$string['grade'] = 'Grade';
$string['timetaken'] = 'Time taken';
$string['preview'] = 'Preview';
$string['gamenotavailable'] = 'The game will not be available until {$a}';
$string['attemptgamenow'] = 'Attempt game now';
$string['reattemptgame'] = 'Reattempt level or attempt new level';
$string['continueattemptgame'] = 'Continue playing';
$string['gamenotavailable'] = 'The game will not be available until {$a}';
$string['gameclosed'] = 'This game closed on {$a}';
$string['gameopenedon'] = 'This game opened at {$a}';
$string['gamecloseson'] = 'This game will close at {$a}';
$string['yourfinalgradeis'] = 'Your final grade for this game is {$a}.';
$string['comment'] = 'Comment';
$string['world'] = 'WORLD';
$string['level'] = 'Level';

//File db/access.php
$string['gabigame:addinstance'] = 'Add a new game';
$string['gabigame:view'] = 'view';
$string['gabigameame:attempt'] = 'Play game';
$string['gabigame:reviewmyattempts'] = 'reviewmyattempts';
$string['gabigame:manage'] = 'Manage';
$string['gabigame:viewreports'] = 'viewreports';
$string['gabigame:viewallscores'] = 'viewallscores';

$string[ 'gabigame:preview'] = 'Preview Games';

// Edit Module Instance
$string['gabigameintro'] = 'Intro';
$string['gabigamename'] = 'Name';
$string['noquizzesincourse'] = 'Please create {$a->linkTag}a new quiz</a> first, before you add an Exabis Game!';
$string['savingdata'] = 'Saving data...';
$string['gametype'] = 'Game-Type';

// lib.php
$string['removescores'] = "remove";

// Config
$string['version_5.2.0_needed'] = 'Gabigame requires at least PHP-Version 5.2.0';

$string['tiles_difficultyLabel'] = 'Fade';
$string['tiles_difficultyLabel_easy'] = 'Slow';
$string['tiles_difficultyLabel_medium'] = 'Average';
$string['tiles_difficultyLabel_hard'] = 'Fast';
$string['tiles_randomizeButton'] = 'Randomize';
$string['tiles_simulateButton'] = 'Simulate';
$string['tiles_resetButton'] = 'Reset';
$string['tiles_saveButton'] = 'Save';
$string['tiles_saveText'] = 'Configuration saved!';

?>
