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
 * @copyright  2022 Gabija Matijaškaitė
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once( __DIR__ .'/locallib.php');

$entityBody = file_get_contents('php://input');

list($statusData, $userData, $gabigameData, $worldData, $levelData, $gameData) = explode("&", $entityBody, 6);

list($temp, $status) = explode("=", $statusData);
list($temp, $userId) = explode("=", $userData);
list($temp, $gabigameId) = explode("=", $gabigameData);
list($temp, $world) = explode("=", $worldData);
list($temp, $level) = explode("=", $levelData);

if($status == "1") { //start	
	gabigame_add_attempt($gabigameId, $userId, $world, $level);
}
else if($status == "2"){ // success	
	list($movesData, $failedData) = explode("&", $gameData);	
	list($temp, $moves) = explode("=", $movesData);
	list($temp, $failedAttempts) = explode("=", $failedData);
	
	gabigame_update_attempts($gabigameId, $userId, $world, $level, $moves, $failedAttempts);
}
?>
