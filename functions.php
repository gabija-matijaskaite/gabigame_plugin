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
 * Basic library.
 *
 * @package    mod_gabigame
 * @copyright  2022 Gabija Matijaškaitė <gabija.matijaskaite@mif.stud.vu.lt>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL');

require_once(dirname(__FILE__).'/lib.php');

/**
 * get user attempts
 *
 * @param integer $gabigameid the gabigame id.
 * @param integer $userid the userid.
 * @param string $status 'all', 'finished' or 'unfinished' to control
 *
 * @return an array of all the user's attempts at this game. Returns an empty array if there are none.
 */
function gabigame_get_user_attempts( $gabigameid, $userid, $status = 'finished', $code = '0') {
    global $DB;

    $statuscondition = array(
        'all' => '',
        'finished' => ' AND timefinish > 0',
        'unfinished' => ' AND timefinish = 0'
    );

    $worldid = substr($code, 0,1);
    $levelid = substr($code, 1,2);

    if($code != '0'){
        $code = 'worldlevel';
    }

    $codecondition = array(
        '0' => '',
        'worldlevel' => ' AND worldid = '.$worldid.' AND levelid = '.$levelid 
    );

    if ($attempts = $DB->get_records_select( 'gabigame_attempts',
        "gabigameid = ? AND userid = ? " .$codecondition[$code].$statuscondition[$status],
            array( $gabigameid, $userid), 'attempt ASC')) {
        return $attempts;
    } else {
        return array();
    }
}

/**
 * Get the best current score for a particular user in a game.
 *
 * @param object $gabigame the game object.
 * @param integer $userid the id of the user.
 *
 * @return float the user's current grade for this game.
 */
function gabigame_get_best_score($gabigame, $userid) {
    global $DB;

    $score = $DB->get_field( 'gabigame_grades', 'score', array( 'gabigameid' => $gabigame->id, 'userid' => $userid));

    if (is_numeric($score)) {
        return $score;
    } else {
        return null;
    }
}

/**
 * Get the best current grade for a particular user in a game.
 *
 * @param object $gabigame the game object.
 * @param integer $userid the id of the user.
 *
 * @return float the user's current grade for this game.
 */
function gabigame_get_best_grade($gabigame, $userid) {
    $score = gabigame_get_best_score($gabigame, $userid);

    if (is_numeric( $score)) {
    return round( $score * $gabigame->grade, 2);
    } else {
        return null;
    }
}

/**
 * Returns an unfinished attempt (if there is one) for the given user.
 *
 * @param integer $gabigameid the id of the gabigame.
 * @param integer $userid the id of the user.
 *
 * @return mixed the unfinished attempt if there is one, false if not.
 */
function gabigame_get_user_attempt_unfinished( $gabigameid, $userid, $code = '0') {
    $attempts = gabigame_get_user_attempts( $gabigameid, $userid, 'unfinished', $code);
    if ($attempts) {
        return array_shift($attempts);
    } else {
        return false;
    }
}

/**
 * Converts score to grade
 *
 * @param float $score
 * @param stdClass $gabigame
 *
 * @return float the user's current grade.
 */
function gabigame_score_to_grade($score, $gabigame) {
    if ($score) {
        return round($score * $gabigame->grade);
    } else {
        return 0;
    }
}

/**
 * Can start a new attempt
 *
 * @param object $gabigame the gabigame object.
 */
function gabigame_can_start_new_attempt( $gabigame) {
    global $DB, $USER;

    if ($gabigame->maxattempts == 0) {
        return true;
    }

    $sql = "SELECT COUNT(*) as c FROM {gabigame_attempts} WHERE gabigameid={$gabigame->id} AND userid={$USER->id}";
    if (($rec = $DB->get_record_sql( $sql)) === false) {
        return true;
    }

    if ($rec->c >= $gabigame->maxattempts) {
        return false;
    } else {
        return true;
    }
}

/**
 * Save the overall grade for a user at a game in the gabigame_grades table
 *
 * @param object $gabigame The game for which the best grade is to be calculated and then saved.
 *
 * @return boolean Indicates success or failure.
 */
function gabigame_save_best_score($gabigameid, $userid) {
    global $DB; 

    if($gabigameid){
        $gabigame  = $DB->get_record('gabigame', array('id' => $gabigameid), '*', MUST_EXIST);
    }
    else{
        throw new moodle_exception('invalidcmorid', 'gabigame');
    }

    // Get all the attempts made by the user.
    if (!$attempts = gabigame_get_user_attempts( $gabigame->id, $userid, 'all')) {
        print_error( 'Could not find any user attempts gabigameid='.$gabigame->id.' userid='.$userid);
    }

    // Calculate the best grade.
    $bestscore = gabigame_calculate_best_score( $gabigame, $attempts); 

    // Save the best grade in the database.
    if ($grade = $DB->get_record('gabigame_grades', array( 'gabigameid' => $gabigame->id, 'userid' => $userid))) {
        $grade->score = round($bestscore, 5);
        $grade->timecreated = time();

        if (!$DB->update_record('gabigame_grades', $grade)) {
            print_error('Could not update best grade');
        }
    } else {
        $grade = new stdClass();
        $grade->gabigameid = $gabigame->id;
        $grade->userid = $userid;
        $grade->score = $bestscore;
        $grade->timecreated = time();
        if (!$DB->insert_record( 'gabigame_grades', $grade)) {
            print_error( 'Could not insert new best grade');
        }
    }

    // Updates gradebook.
    $grades = new stdClass();
    $grades->userid = $userid;
    $grades->rawgrade = gabigame_score_to_grade($bestscore, $gabigame);
    $grades->datesubmitted = time();

    gabigame_grade_item_update( $gabigame, $grades);
    
    return true;
}

/**
 * Calculate the overall score for a game given a number of attempts by a particular user.
 *
 * @return double         The overall score
 * @param object $gabigame    The game for which the best score is to be calculated
 * @param array $attempts An array of all the attempts of the user at the game
 */
function gabigame_calculate_best_score($gabigame, $attempts) {

    foreach($attempts as $attempt){
        $attempt->id = $attempt->worldid * 10 + $attempt->levelid;
    }

    $bestscore = 0;

    for ($x = 11; $x <= 14; $x++) { $bestscore += gabigame_use_grade_method($gabigame->grademethod, $attempts, $x);}   
    for ($x = 21; $x <= 24; $x++) { $bestscore += gabigame_use_grade_method($gabigame->grademethod, $attempts, $x);}   
    for ($x = 31; $x <= 34; $x++) { $bestscore += gabigame_use_grade_method($gabigame->grademethod, $attempts, $x);}
    $bestscore += gabigame_use_grade_method($gabigame->grademethod, $attempts, 41);

    return round($bestscore/11, 6);
}

function gabigame_use_grade_method($grademethod, $attempts, $worldLevelId){    
    $levels = array();

    foreach($attempts as $attempt){
        if($attempt->id == $worldLevelId){
            $levels[] = $attempt;
        }        
    }  

    if(!empty($levels)){
	    switch ($grademethod) {
		case 2: //GAME_GRADEMETHOD_AVERAGE:
		    $sum = 0;
		    $count = 0;
		    foreach ($levels as $attempt) {
		        $sum += $attempt->score;
		        $count++;
		    }

		    return (float)$sum / $count;
		case 3: //GAME_GRADEMETHOD_FIRST:
		    foreach ($levels as $attempt) {
		        return (float)$attempt->score;
		    }
		    break;
		case 4: //GAME_GRADEMETHOD_LAST:
		    foreach ($levels as $attempt) {
		        $final = $attempt->score;
		    }

		    return (float)$final;
		default:
		case 1: //GAME_GRADEMETHOD_HIGHEST
		    $max = 0;
		    foreach ($levels as $attempt) {
		        if ($attempt->score > $max) {
		            $max = $attempt->score;
		        }
		    }

		    return (float)$max;
	    }
    }
    
    return 0;
}

/**
 * get grading option name
 *
 * @param int $option one of the values 1 (GAME_GRADEHIGHEST), 2 (GAME_GRADEAVERAGE), 3 (GAME_ATTEMPTFIRST) or 4 (GAME_ATTEMPTLAST).
 * @return the lang string for that option.
 */
function gabigame_get_grading_option_name($option) {
    if ($option == 0) {
        $option = 1;
    }

    $strings = gabigame_get_grading_options();
    return $strings[$option];
}
?>
