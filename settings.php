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
 * Plugin administration pages are defined here.
 *
 * @package     local_sembasednav
 * @category    admin
 * @copyright   2020 Julian Wendling <julian.wendling@stud.hs-hannover.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
require_once(__DIR__ . '/lib.php');

if ($hassiteconfig) {
    // New settings page.
    $page = new admin_settingpage('sembasednav',
        get_string('pluginname', 'local_sembasednav', null, true));

    if ($ADMIN->fulltree) {
        // Default-Settings auf 0 da ein aktueller Bug besteht, der die Default-Settings nicht übernimmt.
        // Name for courses without a semester
        $setting = new admin_setting_configtext('sembasednav/setting_nosemestername',
            get_string('setting_nosemestername', 'local_sembasednav', null, true),
            get_string('setting_nosemestername_desc', 'local_sembasednav', null, true), 'Ohne Semester', PARAM_TEXT);
        $page->add($setting);

        // Default-Settings auf 0 da ein aktueller Bug besteht, der die Default-Settings nicht übernimmt.
        // Visible semester nodes
        $setting = new admin_setting_configtext('sembasednav/setting_nodescount',
            get_string('setting_nodescount', 'local_sembasednav', null, true),
            get_string('setting_nodescount_desc', 'local_sembasednav', null, true), 0, PARAM_INT);
        $page->add($setting);

        // Default-Settings auf 0 da ein aktueller Bug besteht, der die Default-Settings nicht übernimmt.
        // Open first semester
        $page->add(new admin_setting_configcheckbox('sembasednav/setting_openfirstnode',
            get_string('setting_openfirstnode', 'local_sembasednav', null, true),
            get_string('setting_openfirstnode_desc', 'local_sembasednav', null, true),
            0));

        // Semester order
        $name = 'sembasednav/setting_semesterorder';
        $title = get_string('setting_semesterorder', 'local_sembasednav');
        $description = get_string('setting_semesterorder_desc', 'local_sembasednav');
        $choices[0] = 'Absteigend';
        $choices[1] = 'Aufsteigend';
        $setting = new admin_setting_configselect($name, $title, $description, null, $choices);
        $setting->set_updatedcallback('theme_reset_all_caches');
        $page->add($setting);

        // Position of special nodes
        $name = 'sembasednav/setting_specialNodes';
        $title = get_string('setting_specialNodes', 'local_sembasednav');
        $description = get_string('setting_specialNodes_desc', 'local_sembasednav');
        $choices[0] = 'Oberhalb der Semester';
        $choices[1] = 'Unterhalb der Semester';
        $setting = new admin_setting_configselect($name, $title, $description, null, $choices);
        $setting->set_updatedcallback('theme_reset_all_caches');
        $page->add($setting);
    }

    // Add settings page to the appearance setting category.
    $ADMIN->add('appearance', $page);
}
