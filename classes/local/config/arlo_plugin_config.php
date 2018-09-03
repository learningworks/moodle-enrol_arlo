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

namespace enrol_arlo\local\config;

defined('MOODLE_INTERNAL') || die();

class arlo_plugin_config extends plugin_config {

    const FRANKEN_NAME = 'enrol_arlo'; // Arlo enrolment component.

    /**
     * Plugin settings definition.
     *
     * @return array
     */
    protected static function define_properties() {
        global $CFG;
        return [
            'platform' => [
                'type' => PARAM_RAW
            ],
            'apiusername' => [
                'type' => PARAM_RAW
            ],
            'apipassword' => [
                'type' => PARAM_RAW
            ],
            'apistatus' => [
                'type' => PARAM_INT,
                'default' => -1
            ],
            'apierrormessage' => [
                'type' => PARAM_TEXT,
                'default' => ''
            ],
            'apierrortime' => [
                'type' => PARAM_INT,
                'default' => 0
            ],
            'apierrorcounter' => [
                'type' => PARAM_INT,
                'default' => 0
            ],
            'matchuseraccountsby' => [
                'type' => PARAM_INT
            ],
            'authplugin' => [
                'type' => PARAM_TEXT,
                'default' => 'manual'
            ],
            'roleid' => [
                'type' => PARAM_INT,
                'default' => function() {
                    $student = get_archetype_roles('student');
                    $student = reset($student);
                    return $student->id;
                },
            ],
            'unenrolaction' => [
                'type' => PARAM_INT,
                'default' => ENROL_EXT_REMOVED_UNENROL,
            ],
            'expiredaction' => [
                'type' => PARAM_INT,
                'default' => ENROL_EXT_REMOVED_SUSPEND,
            ],
            'pushonlineactivityresults' => [
                'type' => PARAM_INT,
                'default' => 1
            ],
            'pusheventresults' => [
                'type' => PARAM_INT,
                'default' => 1
            ],
            'alertsiteadmins' => [
                'type' => PARAM_INT,
                'default' => 1
            ],
            'sendnewaccountdetailsemail' => [
                'type' => PARAM_INT,
                'default' => 1
            ],
            'sendemailimmediately' => [
                'type' => PARAM_INT,
                'default' => 1
            ],
            'emailprocessingviacli' => [
                'type' => PARAM_INT,
                'default' => 0
            ]
        ];
    }

    /**
     * Installs plugin configuration defaults.
     *
     * @throws \coding_exception
     */
    public static function install_defaults() {
        $plugin = new static();
        foreach (static::properties_definition() as $property => $settings) {
            $default = static::get_property_default($property);
            if (!is_null($default)) {
                mtrace($property);
                $plugin->raw_set($property, $default);
            }
        }
    }

}
