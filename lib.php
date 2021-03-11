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
 * @package    local_sembasednav
 * @copyright  2020 Julian Wendling, Hochschule Hannover <julian.wendling@stud.hs-hannover.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

function local_sembasednav_extend_navigation(global_navigation $navigation)
{
    global $PAGE, $CFG;

    $myCoursesNode = $navigation->find('mycourses', global_navigation::TYPE_ROOTNODE);
    $myCoursesChildNodes = $myCoursesNode->get_children_key_list();

    $maxSemesterNodes = get_config('sembasednav', 'setting_nodescount');
    $openFirstSemester = get_config('sembasednav', 'setting_openfirstnode');
    $semesterDescending = get_config('sembasednav', 'setting_semesterorder');
    $specialNodesFirst = get_config('sembasednav', 'setting_specialNodes');

    // Register JS to close all child nodes from root node (mycourse)
    $PAGE->requires->js_call_amd('local_sembasednav/sembasednav', 'closeAllChildNodes', [$myCoursesNode->key]);

    try {
        $courses = enrol_get_my_courses();
    } catch (Exception $e) {
        echo $e->getMessage();
        $courses = [];
    }

    // Hides courses that are shown by default
    foreach ($myCoursesChildNodes as $cn) {
        $myCoursesNode->find($cn, null)->showinflatnavigation = false;
    }

    // Nodes that are excluded from max semester count - will always be shown
    $noSemesterAssignedName = get_config('sembasednav', 'setting_nosemestername');
    $specialNodes = ["Semesterunabhängig", $noSemesterAssignedName];
    $specialNodesList = [];
    $mySemesters = [];

    // Sort semester depending on setting
    if ($semesterDescending == 0) {
        // Descending
        usort($courses, function ($a, $b) {
            $aId = get_semester_id($a->id);
            $bId = get_semester_id($b->id);

            return strcasecmp($bId, $aId);
        });
    } else {
        // Ascending
        usort($courses, function ($a, $b) {
            $aId = get_semester_id($a->id);
            $bId = get_semester_id($b->id);

            return strcasecmp($aId, $bId);
        });
    }

    // Assign all modules and semesters
    foreach ($courses as $c) {
        $semesterName = get_semester_name($c->id);

        $courseName = empty($CFG->navshowfullcoursenames) ? $c->shortname : $c->fullname;

        if (in_array($semesterName, $specialNodes, true)) {
            $specialNodesList[$semesterName][] = $courseName;
        } else {
            if (count($mySemesters) >= $maxSemesterNodes) {
                continue;
            }
            $mySemesters[$semesterName][] = $courseName;
        }
    }

    if ($specialNodesFirst == 0)
        add_semester_nodes($specialNodesList, $myCoursesNode);

    // Add all semester nodes
    add_semester_nodes($mySemesters, $myCoursesNode);

    if ($openFirstSemester)
        open_first_semester_node($mySemesters, $myCoursesNode);

    // Add all special nodes
    if ($specialNodesFirst == 1)
        add_semester_nodes($specialNodesList, $myCoursesNode);

    // Sorts course node alphabetically
    usort($courses, function ($a, $b) {
        $aId = $a->shortname;
        $bId = $b->shortname;

        return strcasecmp($aId, $bId);
    });

    // Creates all course nodes and assigns them to their semester node
    foreach ($courses as $c) {
        $semesterName = get_semester_name($c->id);
        $semesterKey = create_node_key($semesterName);
        $semesterNode = $navigation->find($semesterKey, null);

        if (!$semesterNode) {
            continue;
        }

        $courseName = empty($CFG->navshowfullcoursenames) ? $c->shortname : $c->fullname;
        $courseKey = create_node_key($courseName);

        $courseNode = navigation_node::create($courseName, new moodle_url('/course/view.php', array('id' => $c->id)), global_navigation::TYPE_COURSE, $courseName, $courseKey, new pix_icon('i/course', 'grades'));
        $courseNode->showinflatnavigation = true;
        $courseNode->add_class('p-l-3');
        $courseNode->add_class('localboostnavigationcollapsiblechild');
        if (!$semesterNode->forceopen)
            $courseNode->add_class('localboostnavigationcollapsedchild');

        $semesterNode->add_node($courseNode);
    }


}

/**
 * Adds all semester nodes to given parent node and registers
 * them for collapse navigation js
 * @param array $semesterList
 * @param $parentNode
 * @throws coding_exception
 * @throws dml_exception
 */
function add_semester_nodes(array $semesterList, $parentNode)
{
    global $PAGE;

    $collapsenodesforjs = [];

    foreach ($semesterList as $key => $value) {
        $semesterNode = create_semester_node($key);

        if (!in_array($semesterNode->key, $collapsenodesforjs)) {
            $collapsenodesforjs[] = $semesterNode->key;
        }

        $parentNode->add_node($semesterNode);
    }

    // Apply collapse navigation js to every semeseter
    if (!empty($collapsenodesforjs)) {
        $PAGE->requires->js_call_amd('local_boostnavigation/collapsenavdrawernodes', 'init',
            [$collapsenodesforjs, []]);

        foreach ($collapsenodesforjs as $node) {
            user_preference_allow_ajax_update('local_boostnavigation-collapse_' . $node . 'node', PARAM_BOOL);
        }
    }
}

/**
 * @param array $semesterList
 * @param $myCourseNode
 */
function open_first_semester_node(array $semesterList, $myCourseNode)
{
    $semesterKeys = array_keys($semesterList);

    if (empty($semesterKeys[0]))
        return;

    $firstSemesterKey = create_node_key($semesterKeys[0]);

    $semesterNode = $myCourseNode->find($firstSemesterKey, global_navigation::TYPE_CUSTOM);

    $semesterNode->forceopen = true;

    $semesterNode->remove_class('localboostnavigationcollapsedparent');
}

/**
 * Creates a semester node with needed cs classes for collapse navigation js
 * @param string $semesterName
 * @return navigation_node
 * @throws coding_exception
 * @throws dml_exception
 */
function create_semester_node(string $semesterName)
{
    $localBoostnavigationConfig = get_config('local_boostnavigation');
    $userprefmycoursesnode = get_user_preferences('local_boostnavigation-collapse_mycoursesnode',
        $localBoostnavigationConfig->collapsemycoursesnodedefault);

    $semesterKey = create_node_key($semesterName);

    $semesterNode = navigation_node::create($semesterName, new moodle_url('/course/view.php', null), global_navigation::TYPE_CUSTOM, null, $semesterKey, null);
    $semesterNode->showinflatnavigation = true;

    $semesterNode->add_class('localboostnavigationcollapsibleparent');
    $semesterNode->add_class('localboostnavigationcollapsedparent');
    $semesterNode->add_class('localboostnavigationcollapsiblechild');

    if ($userprefmycoursesnode == 1) {
        $semesterNode->add_class('localboostnavigationcollapsedchild');
    }

    return $semesterNode;
}

/**
 * Creates a whitespace and slash free semester key
 * @param string $semesterName
 * @return string
 */
function create_node_key(string $semesterName)
{
    return "node-" . str_replace([" ", "/"], "-", $semesterName);
}

/**
 * Returns semester name of given course id.
 * If no semester is found, return no semester assigned name.
 * @param int $id
 * @return string
 */
function get_semester_name(int $id)
{
    global $DB;

    $noSemesterAssigned = get_config('sembasednav', 'setting_nosemestername');
    $semesterName = '';

    try {
        $semFieldName = $DB->get_record('customfield_field', array('type' => 'semester'), '*', MUST_EXIST);
    } catch (Exception $e) {
        return $noSemesterAssigned;
    }

    try {
        $allCustomFields = get_course_metadata($id);
        $semesterField = $allCustomFields[$semFieldName->shortname];
        $semesterValue = preg_replace('/[^0-9]/', '', $semesterField); // extract semester int from string
        $semesterName = \customfield_semester\data_controller::get_name_for_semester((int)$semesterValue);

    } catch (Exception $e) {
    }

    return empty($semesterName) || $semesterName === '' ? $noSemesterAssigned : $semesterName;
}

/**
 * @param int $id
 * @return string
 */
function get_semester_id(int $id)
{
    global $DB;

    $semesterValue = '';

    try {
        $semFieldName = $DB->get_record('customfield_field', array('type' => 'semester'), '*', MUST_EXIST);
    } catch (Exception $e) {
        return 0;
    }

    try {
        $allCustomFields = get_course_metadata($id);
        $semesterField = $allCustomFields[$semFieldName->shortname];
        $semesterValue = preg_replace('/[^0-9]/', '', $semesterField); // extract semester int from string
    } catch (Exception $e) {
    }

    return empty($semesterValue) || $semesterValue === '' ? 0 : $semesterValue;
}

// https://docs.moodle.org/dev/Custom_fields_API#Example_code_for_course_custom_fields
/**
 * Used to access course semester field
 * @param $courseid
 * @return array
 * @throws moodle_exception
 */
function get_course_metadata($courseid)
{
    $handler = \core_customfield\handler::get_handler('core_course', 'course');
    $data = $handler->get_instance_data($courseid);
    $metadata = [];
    foreach ($data as $d) {
        if (empty($d->get_value())) {
            continue;
        }
        $cat = $d->get_field()->get_category()->get('name');
        $metadata[$d->get_field()->get('shortname')] = $cat . ': ' . $d->get_value();
    }
    return $metadata;
}