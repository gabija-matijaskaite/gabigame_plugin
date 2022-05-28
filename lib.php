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
 * Library of interface functions and constants for module gabigame
 *
 * All the core Moodle functions, neeeded to allow the module to work
 * integrated in Moodle should be placed here.
 * All the gabigame specific functions, needed to implement all the module
 * logic, should go to locallib.php. This will help to save some memory when
 * Moodle is performing actions across all modules.
 *
 * @package    mod_gabigame
 * @copyright  2022 Gabija Matijaškaitė <gabija.matijaskaite@mif.stud.vu.lt>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
 
defined('MOODLE_INTERNAL');

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
 require_once($CFG->libdir . '/questionlib.php');
 require_once($CFG->dirroot.'/lib/completionlib.php');

/**
 * Returns the information on whether the module supports a feature
 *
 * @see plugin_supports() in lib/moodlelib.php
 * @param string $feature FEATURE_xx constant for requested feature
 * @return mixed true if the feature is supported, null if unknown
 */
function gabigame_supports($feature) {
    switch($feature) {
        case FEATURE_COMPLETION_TRACKS_VIEWS:
            return true;
        case FEATURE_COMPLETION_HAS_RULES:
            return true;
        case FEATURE_MOD_INTRO:
            return true;
        case FEATURE_SHOW_DESCRIPTION:
            return true;
        case FEATURE_BACKUP_MOODLE2:
            return true;
		case FEATURE_GRADE_HAS_GRADE:
			return true;
        default:
            return null;
    }
}

/**
 * Saves a new instance of the gabigame into the database
 *
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will create a new instance and return the id number
 * of the new instance.
 *
 * @param stdClass $gabigame An object from the form in mod_form.php
 * @param mod_gabigame_mod_form $mform The add instance form
 * @return int The id of the newly inserted gabigame record
 */
function gabigame_add_instance(stdClass $gabigame, mod_gabigame_mod_form $mform = null) {
    global $DB;

    $gabigame->timecreated = time();
    $id = $DB->insert_record('gabigame', $gabigame);
    $gabigame = $DB->get_record_select( 'gabigame', "id=$id");

	gabigame_grade_item_update($gabigame);

    return $id;
}

/**
 * Updates an instance of the gabigame in the database
 *
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will update an existing instance with new data.
 *
 * @param stdClass $gabigame An object from the form in mod_form.php
 * @param mod_gabigame_mod_form $mform
 * @return boolean Success/Fail
 */
function gabigame_update_instance(stdClass $gabigame, mod_gabigame_mod_form $mform = null) {
    global $DB;

    $gabigame->timemodified = time();
    $gabigame->id = $gabigame->instance;
	
    if ($gabigame->grade == '') {
        $gabigame->grade = 0;
    }

    if (!$DB->update_record("gabigame", $gabigame)) {
        return false;
    }
	
	gabigame_grade_item_update( $gabigame);

	return true;
}

/**
 * Removes an instance of the gabigame from the database
 *
 * Given an ID of an instance of this module,
 * this function will permanently delete the instance
 * and any data that depends on it.
 *
 * @param int $id Id of the module instance
 * @return boolean Success/Failure
 */
function gabigame_delete_instance($id) {
    global $DB;

    if (! $gabigame = $DB->get_record('gabigame', array('id' => $id))) {
        return false;
    }

    $DB->delete_records('gabigame', array('id' => $gabigame->id));
    $DB->delete_records('gabigame_scores', array('gabigameid' => $gabigame->id));

    return true;
}

/**
 * Obtains the automatic completion state for this gabigame based on any conditions
 * in gabigame settings.
 *
 * @param object $course Course
 * @param object $cm Course-module
 * @param int $userid User ID
 * @param bool $type Type of comparison (or/and; can be used as return value if no conditions)
 * @return bool True if completed, false if not. (If no conditions, then return
 *   value depends on comparison type)
 */
function gabigame_get_completion_state($course, $cm, $userid, $type) {
    global $DB;

    // Get gabigame details.
    if (!($gabigame = $DB->get_record('gabigame', array('id' => $cm->instance)))) {
        throw new Exception("Can't find gabigame {$cm->instance}");
    }

    // Default return value.
    $result = $type;
    if ($gabigame->completionscore) {
        $where = ' gabigameid = :gabigameid AND userid = :userid AND score >= :score';
        $params = array(
            'gabigameid' => $gabigame->id,
            'userid' => $userid,
            'score' => $gabigame->completionscore,
        );
        $value = $DB->count_records_select('gabigame_grades', $where, $params) > 0;
        if ($type == COMPLETION_AND) {
            $result = $result && $value;
        } else {
            $result = $result || $value;
        }
    }

    return $result;
}

/**
 * Given a course and a time, this module should find recent activity
 * that has occurred in gabigame activities and print it out.
 * Return true if there was output, or false is there was none.
 *
 * @param stdClass $course The course record.
 * @param bool $viewfullnames boolean to determine whether to show full names or not
 * @param int $timestart the time the rendering started
 * @return boolean True if the activity was printed, false otherwise
 */
function gabigame_print_recent_activity($course, $viewfullnames, $timestart) {
    return false;  // True if anything was printed, otherwise false.
}

/**
 * Prepares the recent activity data
 *
 * This callback function is supposed to populate the passed array with
 * custom activity records. These records are then rendered into HTML via
 * {gabigame_print_recent_mod_activity()}.
 *
 * @param array $activities sequentially indexed array of objects with the 'cmid' property
 * @param int $index the index in the $activities to use for the next record
 * @param int $timestart append activity since this time
 * @param int $courseid the id of the course we produce the report for
 * @param int $cmid course module id
 * @param int $userid check for a particular user's activity only, defaults to 0 (all users)
 * @param int $groupid check for a particular group's activity only, defaults to 0 (all groups)
 * @return void adds items into $activities and increases $index
 */
function gabigame_get_recent_mod_activity(&$activities, &$index, $timestart, $courseid, $cmid, $userid=0, $groupid=0) {
}

/**
 * Prints single activity item prepared by gabigame_get_recent_mod_activity
 *
 * @see gabigame_get_recent_mod_activity()
 *
 * @param object $activity The activity object of the gabigame
 * @param int $courseid The id of the course the gabigame resides in
 * @param bool $detail not used, but required for compatibilty with other modules
 * @param int $modnames not used, but required for compatibilty with other modules
 * @param bool $viewfullnames boolean to determine whether to show full names or not
 * @return void
 */
function gabigame_print_recent_mod_activity($activity, $courseid, $detail, $modnames, $viewfullnames) {
}

/**
 * Function to be run periodically according to the moodle cron
 * This function searches for things that need to be done, such
 * as sending out mail, toggling flags etc ...
 *
 * @return boolean
 * @todo Finish documenting this function
 **/
function gabigame_cron () {
    return true;
}

/**
 * Return grade for given user or all users.
 *
 * @param stdClass $gabigame
 * @param int $userid optional user id, 0 means all users
 * @return array array of grades, false if none
 */
function gabigame_get_user_grades($gabigame, $userid=0) {
    global $DB;

    $user = $userid ? "AND u.id = $userid" : "";

    if (!isset( $gabigame->grade)) {
        $gabigame->grade = 1;
    }
    $sql = 'SELECT u.id, u.id AS userid, '.$gabigame->grade.
            ' * g.score AS rawgrade, g.timemodified AS dategraded, MAX(a.timefinish) AS datesubmitted
            FROM {user} u, {gabigame_grades} g, {gabigame_attempts} a
            WHERE u.id = g.userid AND g.gabigameid = '.$gabigame->id.' AND a.gabigameid = g.gabigameid AND u.id = a.userid';
    if ($userid != 0) {
        $sql .= ' AND u.id='.$userid;
    }
    $sql .= ' GROUP BY u.id, g.score, g.timemodified';

    return $DB->get_records_sql( $sql);
}

/**
 * Converts grade to score.
 *
 * @param stdClass $gabigame
 * @param float $grade
 *
 * @return foat score
 */
function gabigame_format_grade($gabigame, $grade) {
    return format_float($grade, 0);
}

/**
 * get grading options
 *
 * @return the options for calculating the quiz grade from the individual attempt grades.
 */
function gabigame_get_grading_options() {
    return array (
            1 => get_string('gradehighest', 'gabigame'),
            2 => get_string('gradeaverage', 'gabigame'),
            3 => get_string('attemptfirst', 'gabigame'),
            4 => get_string('attemptlast', 'gabigame'));
}

/**
 * Returns all other caps used in the module
 *
 * e.g. array('moodle/site:accessallgroups');
 * @return array of capabilities used in the module
 */
function gabigame_get_extra_capabilities() {
    return array();
}

// Gradebook API.
/**
 * Update gabigame grades in the gradebook
 *
 * Needed by grade_update_mod_grades() in lib/gradelib.php
 *
 * @param stdClass $gabigame instance object with extra cmidnumber and modname property
 * @param int $userid update grade of specific user only, 0 means all participants
 * @return void
 */
function gabigame_update_grades(stdClass $gabigame, $userid = 0, $nullifnone=true) {
    global $CFG;
	
    if (!function_exists('grade_update')) { // Workaround for buggy PHP versions.
        if (file_exists( $CFG->libdir.'/gradelib.php')) {
            require_once($CFG->libdir.'/gradelib.php');
        } else {
            return;
        }
    }

    if ($gabigame != null) {
        $grades = gabigame_get_user_grades($gabigame, $userid);
        if ( $grades != null) {
            gabigame_grade_item_update($gabigame, $grades);

        } else if ($userid and $nullifnone) {
            $grade = new stdClass;
            $grade->userid   = $userid;
            $grade->rawgrade = null;
            gabigame_grade_item_update( $gabigame, $grade);

        } else {
            gabigame_grade_item_update( $gabigame);
        }

    } else {
        $sql = "SELECT a.*, cm.idnumber as cmidnumber, a.course as courseid
                  FROM {gabigame} a, {course_modules} cm, {modules} m
                 WHERE m.name='gabigame' AND m.id=cm.module AND cm.instance=a.id";
        if ($rs = $DB->get_recordset_sql( $sql)) {
            while ($gabigame = $DB->rs_fetch_next_record( $rs)) {
                if ($gabigame->grade != 0) {
                    gabigame_update_grades( $gabigame, 0, false);
                } else {
                    gabigame_grade_item_update( $gabigame);
                }
            }
            $DB->rs_close( $rs);
        }
    }
}

/**
 * Creates or updates grade item for the give gabigame instance
 *
 * Needed by grade_update_mod_grades() in lib/gradelib.php
 *
 * @param stdClass $gabigame instance object with extra cmidnumber and modname property
 * @param mixed $grades optional array/object of grade(s); 'reset' means reset grades in gradebook
 * @return void
 */
function gabigame_grade_item_update(stdClass $gabigame, $grades=null) {
    global $CFG;

    if (!function_exists('grade_update')) { // Workaround for buggy PHP versions.
        if (file_exists( $CFG->libdir.'/gradelib.php')) {
            require_once($CFG->libdir.'/gradelib.php');
        } else {
            return;
        }
    }

    if (property_exists('cmidnumber','gabigame')) 
    { // Tt may not be always present.
        $params = array('itemname' => $gabigame->name, 'idnumber' => $gabigame->cmidnumber);
    } else {
        $params = array('itemname' => $gabigame->name);
    }
	
    if ($gabigame->grade > 0) {
        $params['gradetype'] = GRADE_TYPE_VALUE;
        $params['grademax']  = $gabigame->grade;
        $params['grademin']  = 0;

    } else {
        $params['gradetype'] = GRADE_TYPE_NONE;
    }

    if ($grades === 'reset') {
        $params['reset'] = true;
        $grades = null;
    }

    return grade_update('mod/gabigame', $gabigame->course, 'mod', 'gabigame', $gabigame->id, 0, $grades, $params);
}

// File API.

/**
 * Returns the lists of all browsable file areas within the given module context
 *
 * The file area 'intro' for the activity introduction field is added automatically
 * by {file_browser::get_file_info_context_module()}
 *
 * @param stdClass $course
 * @param stdClass $cm
 * @param stdClass $context
 * @return array of [(string)filearea] => (string)description
 */
function gabigame_get_file_areas($course, $cm, $context) {
    return array();
}

/**
 * File browsing support for gabigame file areas
 *
 * @package mod_gabigame
 * @category files
 *
 * @param file_browser $browser
 * @param array $areas
 * @param stdClass $course
 * @param stdClass $cm
 * @param stdClass $context
 * @param string $filearea
 * @param int $itemid
 * @param string $filepath
 * @param string $filename
 * @return file_info instance or null if not found
 */
function gabigame_get_file_info($browser, $areas, $course, $cm, $context, $filearea, $itemid, $filepath, $filename) {
    return null;
}

/**
 * Serves the files from the gabigame file areas
 *
 * @package mod_gabigame
 * @category files
 *
 * @param stdClass $course the course object
 * @param stdClass $cm the course module object
 * @param stdClass $context the gabigame's context
 * @param string $filearea the name of the file area
 * @param array $args extra arguments (itemid, path)
 * @param bool $forcedownload whether or not force download
 * @param array $options additional options affecting the file serving
 */
function gabigame_pluginfile($course, $cm, $context, $filearea, array $args, $forcedownload, array $options=array()) {

    if ($context->contextlevel != CONTEXT_MODULE) {
        send_file_not_found();
    }

    require_login($course, true, $cm);

    send_file_not_found();
}

// Navigation API.

/**
 * Extends the global navigation tree by adding gabigame nodes if there is a relevant content
 *
 * This can be called by an AJAX request so do not rely on $PAGE as it might not be set up properly.
 *
 * @param navigation_node $navref An object representing the navigation tree node of the gabigame module instance
 * @param stdclass $course The course in which navigation is currently being extended
 * @param stdclass $module The module in which navigation is currently being extended
 * @param cm_info $cm The course module info
 * @return void
 */
function gabigame_extend_navigation(navigation_node $navref, stdclass $course, stdclass $module, cm_info $cm) {
}

/**
 * Extends the settings navigation with the gabigame settings
 *
 * This function is called when the context for the page is a gabigame module. This is not called by AJAX
 * so it is safe to rely on the $PAGE.
 *
 * @param settings_navigation $settingsnav {settings_navigation}
 * @param navigation_node $gabigamenode {navigation_node}
 */
function gabigame_extend_settings_navigation(settings_navigation $settingsnav, navigation_node $gabigamenode=null) {
}

/**
 * Implementation of the function for printing the form elements that control
 * whether the course reset functionality affects the gabigame.
 * @param stdClass $mform form passed by reference
 */
function gabigame_reset_course_form_definition(&$mform) {

    $mform->addElement('header', 'gabigameheader', get_string('modulenameplural', 'gabigame'));
    $mform->addElement('advcheckbox', 'reset_gabigame_scores', get_string('removescores', 'gabigame'));

}

/**
 * Course reset form defaults.
 * @param stdClass $course
 * @return array
 */
function gabigame_reset_course_form_defaults($course) {
    return array('reset_gabigame_scores' => 1);

}

/**
 * Actual implementation of the rest coures functionality, delete all the
 * gabigame responses for course $data->courseid.
 *
 * @param stdClass $data the data submitted from the reset course.
 * @return array status array
 */
function gabigame_reset_userdata($data) {
    global $DB;
        $componentstr = get_string('modulenameplural', 'gabigame');
        $status = array();

    if (!empty($data->reset_gabigame_scores)) {
        $scoresql = "SELECT qg.id
                     FROM {gabigame} qg
                     WHERE qg.course=?";

        $DB->delete_records_select('gabigame_grades', "gabigameid IN ($scoresql)", array($data->courseid));
        $status[] = array('component' => $componentstr, 'item' => get_string('removescores', 'gabigame'), 'error' => false);
    }

    return $status;
}

/**
 * Returns the context instance of a Module. Is the same for all version of Moodle.
 *
 * This is used to find out if scale used anywhere
 *
 * @param int $moduleid
 * @return stdClass context
 */
function gabigame_get_context_module_instance( $moduleid) {

    if (class_exists( 'context_module')) {
        return context_module::instance( $moduleid);
    }

    return get_context_instance( CONTEXT_MODULE, $moduleid);
}

/**
 * Returns the context instance of a course. Is the same for all version of Moodle.
 *
 * This is used to find out if scale used anywhere
 *
 * @param int $courseid
 *
 * @return stdClass context
 */
function gabigame_get_context_course_instance( $courseid) {
    if (class_exists( 'context_course')) {
        return context_course::instance( $courseid);
    }

    return get_context_instance( CONTEXT_COURSE, $courseid);
}

/**
 * This function receives a calendar event and returns the action associated with it, or null if there is none.
 *
 * This is used by block_myoverview in order to display the event appropriately. If null is returned then the event
 * is not displayed on the block.
 *
 * @param calendar_event $event
 * @param \core_calendar\action_factory $factory
 * @param int $userid User id to use for all capability checks, etc. Set to 0 for current user (default).
 * @return \core_calendar\local\event\entities\action_interface|null
 */
function mod_gabigame_core_calendar_provide_event_action(calendar_event $event,
                                                    \core_calendar\action_factory $factory,
                                                    int $userid = 0) {
    global $USER;
    if (!$userid) {
        $userid = $USER->id;
    }
    $cm = get_fast_modinfo($event->courseid, $userid)->instances['gabigame'][$event->instance];
    if (!$cm->uservisible) {
        // The module is not visible to the user for any reason.
        return null;
    }
    $completion = new \completion_info($cm->get_course());
    $completiondata = $completion->get_data($cm, false, $userid);
    if ($completiondata->completionstate != COMPLETION_INCOMPLETE) {
        return null;
    }
    return $factory->create_instance(
        get_string('view'),
        new \moodle_url('/mod/gabigame/view.php', ['id' => $cm->id]),
        1,
        true
    );
}

/**
 * Callback which returns human-readable strings describing the active completion custom rules for the module instance.
 *
 * @param object $cm the cm_info object.
 * @return array $descriptions the array of descriptions for the custom rules.
 */
function mod_gabigame_get_completion_active_rule_descriptions($cm) {
    // Values will be present in cm_info, and we assume these are up to date.
    if (!$cm instanceof cm_info || !isset($cm->customdata['customcompletionrules'])
        || $cm->completion != COMPLETION_TRACKING_AUTOMATIC) {
        return [];
    }

    $descriptions = [];
    foreach ($cm->customdata['customcompletionrules'] as $key => $val) {
        switch ($key) {
            case 'completionscore':
                if (!empty($val)) {
                    $descriptions[] = get_string('completionscoredesc', 'gabigame', $val);
                }
                break;
            default:
                break;
        }
    }
    return $descriptions;
}

/**
 * Add a get_coursemodule_info function in case any pcast type wants to add 'extra' information
 * for the course (see resource).
 *
 * Given a course_module object, this function returns any "extra" information that may be needed
 * when printing this activity in a course listing.  See get_array_of_activities() in course/lib.php.
 *
 * @param stdClass $coursemodule The coursemodule object (record).
 * @return cached_cm_info An object on information that the courses
 *                        will know about (most noticeably, an icon).
 */
function gabigame_get_coursemodule_info($coursemodule) {
    global $DB;

    $dbparams = ['id' => $coursemodule->instance];
    $fields = 'id, name, intro, introformat, completionscore';
    if (!$gabigame = $DB->get_record('gabigame', $dbparams, $fields)) {
        return false;
    }

    $result = new cached_cm_info();
    $result->name = $gabigame->name;

    if ($coursemodule->showdescription) {
        // Convert intro to html. Do not filter cached version, filters run at display time.
        $result->content = format_module_intro('gabigame', $gabigame, $coursemodule->id, false);
    }

    // Populate the custom completion rules as key => value pairs, but only if the completion mode is 'automatic'.
    if ($coursemodule->completion == COMPLETION_TRACKING_AUTOMATIC) {
        $result->customdata['customcompletionrules']['completionscore'] = $gabigame->completionscore;
    }

    return $result;
}

/**
 * Function to prepare strings to be printed out as JSON.
 *
 * @param stdClass $string The string to be cleaned
 * @return string The string, ready to be printed as JSON
 */
function gabigame_cleanup($string) {
    $string = strip_tags($string);
    $string = preg_replace('/"/', '\"', $string);
    $string = preg_replace('/[\n\r]/', ' ', $string);
    $string = stripslashes($string);
    return $string;
}

/**
 * Function to record the player starting the gabigame.
 * @param stdClass $gabigame
 * @return boolean
 */
function gabigame_log_game_start($gabigame) {
    global $DB;

    $cm = get_coursemodule_from_instance('gabigame', $gabigame->id, 0, false, MUST_EXIST);
    $course     = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
    $context = context_module::instance($cm->id);

    // Trigger the game score added event.
    $event = \mod_gabigame\event\game_started::create(array(
        'objectid' => $gabigame->id,
        'context' => $context,
    ));

    $event->add_record_snapshot('gabigame', $gabigame);
    $event->trigger();

    return true;
}
?>
