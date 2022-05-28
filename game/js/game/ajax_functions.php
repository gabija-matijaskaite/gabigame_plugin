<?php
/**
 * File for writing or updating database from game with AJAX.
 *
 * @package    mod_gabigame
 * @copyright  2022 Gabija Matijaškaitė <gabija.matijaskaite@mif.stud.vu.lt>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


require_once(dirname(__FILE__).'/../../../../../config.php');
require_once(dirname(__FILE__).'/../../../functions.php');

/**
 * Inserts a record to gabigame_attempts.
 *
 * @param int $gabigameId
 * @param int $userId
 * @param int $world
 * @param int $level
 */
function gabigame_add_attempt($gabigameId, $userId, $world, $level) {	
    global $DB;

	$attempt = $DB->get_record_select('gabigame_attempts', 
                                        "gabigameid=$gabigameId 
                                        AND userid=$userId 
                                        AND worldid=$world 
                                        AND levelid=$level
                                        AND timefinish=0", 
                                        null, 
                                        'id'
                                    );

    if($attempt == false){ // if this level wasn't started insert new attempt
        $newrec = new stdClass();
        $newrec->gabigameid = $gabigameId;
        $newrec->userid = $userId;
        $newrec->worldid = $world;
        $newrec->levelid = $level;
        $newrec->timestart = time();
        $newrec->timefinish = 0;

        $params = array('gabigameid' => $gabigameId, 
                        'userid' => $userId, 
                        'worldid' => $world, 
                        'levelid' => $level
                        );
        $newrec->attempt = $DB->get_field( 'gabigame_attempts', 'max(attempt)', $params) + 1;
        $newrec->score = 0;

        if(!($newid = $DB->insert_record( 'gabigame_attempts', $newrec))) {
            print_error("Insert gabigame_attempts: new rec not inserted");
        }    
    }
}

/**
 * Updates table gabigame_attempts
 *
 * @param int $gabigameId
 * @param int $userId
 * @param int $world
 * @param int $level
 * @param int $moves
 * @param int $failedAttempts
 */
function gabigame_update_attempts($gabigameId, $userId, $world, $level, $moves, $failedAttempts) {
    global $DB;
	
	$attempt = $DB->get_record_select('gabigame_attempts', 
                                        "gabigameid=$gabigameId 
                                        AND userid=$userId 
                                        AND worldid=$world 
                                        AND levelid=$level
                                        AND timefinish=0", 
                                        null, 
                                        'id'
                                    );
	  
	if($attempt != false) {
        //Calculation of score
        $scoreCalculated = 0;
        if($failedAttempts == 0){
            $scoreCalculated = 1;
        }
        elseif($failedAttempts > 10){
            $scoreCalculated = 1/100;
        }
        else{
            $movesValue = 9/(1+($moves/100));
            $scoreCalculated = round((100 + $movesValue - (10 * $failedAttempts))/100, 6);
        }

        $updrec = new stdClass();
        $updrec->id = $attempt->id;
        $updrec->timefinish = time();
		$updrec->score = $scoreCalculated;
		$updrec->moves = $moves;
		$updrec->failedattempts = $failedAttempts;

        if (!$DB->update_record( 'gabigame_attempts', $updrec)) {
            print_error( "gabigame_updateattempts: Can't update gabigame_attempts id=$updrec->id");
        }
    }

    // Update table gabigame_grades.
    gabigame_save_best_score($gabigameId, $userId);
}
?>
