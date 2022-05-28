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
 * The main gabigame configuration form
 *
 * It uses the standard core Moodle formslib. For more info about them, please
 * visit: http://docs.moodle.org/en/Development:lib/formslib.php
 *
 * @package    mod_gabigame
 * @author     Alastair Munro <alastair@catalyst.net.nz>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/course/moodleform_mod.php');
require_once($CFG->dirroot.'/lib/questionlib.php');

/**
 * Module instance settings form
 * @copyright  2022 Gabija Matijaškaitė <gabija.matijaskaite@mif.stud.vu.lt>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_gabigame_mod_form extends moodleform_mod {

    /**
     * Defines forms elements
     */
    public function definition() {
        global $CFG, $DB, $COURSE;

        $mform = $this->_form;

        // Adding the "general" fieldset, where all the common settings are showed.
        $mform->addElement('header', 'general', get_string('general', 'form'));

        // Adding the standard "name" field.
        $mform->addElement('text', 'name', get_string('gabigamename', 'gabigame'), array('size' => '64'));
        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEAN);
        }
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');
        $mform->addHelpButton('name', 'gabigamename', 'gabigame');

        // Adding the standard "intro" and "introformat" fields.
        if ($CFG->branch >= 29) {
            $this->standard_intro_elements();
        } else {
            $this->add_intro_editor();
        }
		
        // Grade options.
        $this->standard_grading_coursemodule_elements();
        $mform->removeElement('grade');
        $mform->addElement('text', 'grade', get_string( 'grademax', 'grades'), array('size' => 4));
        $mform->setType('grade', PARAM_INT);
		
        $gradingtypeoptions = array();
        $gradingtypeoptions[1] = get_string('gradehighest', 'gabigame');
        $gradingtypeoptions[2] = get_string('gradeaverage', 'gabigame');
        $gradingtypeoptions[3] = get_string('attemptfirst', 'gabigame');
        $gradingtypeoptions[4] = get_string('attemptlast', 'gabigame');
        $mform->addElement('select', 'grademethod', get_string('grademethod', 'gabigame'), $gradingtypeoptions);
        
		
		$context = context_course::instance($COURSE->id);

        // Add standard elements, common to all modules.
        $this->standard_coursemodule_elements();
        // Add standard buttons, common to all modules.
        $this->add_action_buttons();
    }
}
?>
