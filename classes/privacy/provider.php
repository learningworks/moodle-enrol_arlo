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
 * Privacy provider class for Arlo enrolment plugin.
 *
 * @package   enrol_arlo {@link https://docs.moodle.org/dev/Frankenstyle}
 * @copyright 2019 LearningWorks Ltd {@link http://www.learningworks.co.nz}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace enrol_arlo\privacy;

defined('MOODLE_INTERNAL') || die();

use context;
use context_course;
use context_user;
use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;

class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\plugin\provider,
    \core_privacy\local\request\core_userlist_provider {

    public static function get_metadata(collection $collection) : collection {
        $collection->add_database_table(
            'enrol_arlo_contact',
            [
                'userid'        => 'privacy:metadata:enrol_arlo_contact:userid',
                'sourceid'      => 'privacy:metadata:enrol_arlo_contact:sourceid',
                'sourceguid'    => 'privacy:metadata:enrol_arlo_contact:sourceguid',
                'firstname'     => 'privacy:metadata:enrol_arlo_contact:firstname',
                'lastname'      => 'privacy:metadata:enrol_arlo_contact:lastname',
                'email'         => 'privacy:metadata:enrol_arlo_contact:email',
                'codeprimary'   => 'privacy:metadata:enrol_arlo_contact:codeprimary',
                'phonework'     => 'privacy:metadata:enrol_arlo_contact:phonework',
                'phonemobile'   => 'privacy:metadata:enrol_arlo_contact:phonemobile'

            ],
            'privacy:metadata:enrol_arlo_contact'
        );
        $collection->add_database_table(
            'enrol_arlo_emailqueue',
            [
                'area'       => 'privacy:metadata:enrol_arlo_emailqueue:area',
                'instanceid' => 'privacy:metadata:enrol_arlo_emailqueue:instanceid',
                'userid'     => 'privacy:metadata:enrol_arlo_emailqueue:userid',
                'type'       => 'privacy:metadata:enrol_arlo_emailqueue:type',
                'status'     => 'privacy:metadata:enrol_arlo_emailqueue:status',
                'extra'      => 'privacy:metadata:enrol_arlo_emailqueue:extra'

            ],
            'privacy:metadata:enrol_arlo_emailqueue'
        );
        $collection->add_database_table(
            'enrol_arlo_registration',
            [
                'enrolid'           => 'privacy:metadata:enrol_arlo_registration:enrolid',
                'userid'            => 'privacy:metadata:enrol_arlo_registration:userid',
                'sourceid'          => 'privacy:metadata:enrol_arlo_registration:sourceid',
                'sourceguid'        => 'privacy:metadata:enrol_arlo_registration:sourceguid',
                'grade'             => 'privacy:metadata:enrol_arlo_registration:grade',
                'outcome'           => 'privacy:metadata:enrol_arlo_registration:outcome',
                'lastactivity'      => 'privacy:metadata:enrol_arlo_registration:lastactivity',
                'progressstatus'    => 'privacy:metadata:enrol_arlo_registration:progressstatus',
                'progresspercent'   => 'privacy:metadata:enrol_arlo_registration:progresspercent',
                'sourcecontactid'   => 'privacy:metadata:enrol_arlo_registration:sourcecontactid',
                'sourcecontactguid' => 'privacy:metadata:enrol_arlo_registration:sourcecontactguid'
            ],
            'privacy:metadata:enrol_arlo_registration'
        );
        $collection->add_subsystem_link('core_group', [], 'privacy:metadata:core_group');
        return $collection;
    }

    /**
     * Get the list of contexts that contain user information for the specified user.
     *
     * @param int $userid
     * @return contextlist
     */
    public static function get_contexts_for_userid(int $userid) : contextlist {
        $contextlist = new contextlist();
        $sql = "SELECT ctx.id
                  FROM {context} ctx
                  JOIN {user} u ON u.id = ctx.instanceid AND ctx.contextlevel = :contextuser
                  JOIN {enrol_arlo_contact} eac ON eac.userid = u.id
                 WHERE u.id = :userid";
        $params = [
            'contextuser' => CONTEXT_USER,
            'userid'      => $userid,
        ];
        $contextlist->add_from_sql($sql, $params);
        $sql = "SELECT ctx.id
                  FROM {context} ctx
                  JOIN {enrol} e ON e.courseid = ctx.instanceid AND ctx.contextlevel = :contextcourse
                  JOIN {enrol_arlo_registration} ear ON ear.enrolid = e.id
                  JOIN {enrol_arlo_contact} eac ON eac.userid = ear.userid
                  JOIN {user} u ON u.id = eac.userid
                 WHERE u.id = :userid";
        $params = [
            'contextcourse' => CONTEXT_COURSE,
            'userid'        => $userid,
        ];
        $contextlist->add_from_sql($sql, $params);
        return $contextlist;
    }

    /**
     * Get the list of users who have data within a context.
     *
     * @param userlist $userlist
     */
    public static function get_users_in_context(userlist $userlist) {
        $context = $userlist->get_context();
        if ($context instanceof context_user) {
            $sql = "SELECT u.id
                      FROM {enrol_arlo_contact} eac
                      JOIN {user} u ON u.id = eac.userid
                     WHERE u.id = :userid";
            $params = ['userid' => $context->instanceid];
            $userlist->add_from_sql('id', $sql, $params);
        }
        if ($context instanceof context_course) {
            $sql = "SELECT u.id
                      FROM {enrol_arlo_registration} ear
                      JOIN {enrol_arlo_contact} eac ON eac.userid = ear.userid
                      JOIN {enrol} e ON e.id = ear.enrolid
                      JOIN {user} u ON u.id = ear.userid
                     WHERE e.courseid = :courseid";
            $params = ['courseid' => $context->instanceid];
            $userlist->add_from_sql('id', $sql, $params);
        }
    }
    
    /**
     * Export all user data for the specified user, in the specified contexts.
     *
     * @param approved_contextlist $contextlist
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public static function export_user_data(approved_contextlist $contextlist) {
        global $DB;
        if (empty($contextlist->count())) {
            return;
        }
        $user = $contextlist->get_user();
        foreach ($contextlist->get_contexts() as $context) {
            list($contextsql, $contextparams) = $DB->get_in_or_equal($context->id, SQL_PARAMS_NAMED);
            if ($context instanceof context_user) {
                // Associated Contact information.
                $sql = "SELECT eac.*
                          FROM {context} ctx
                          JOIN {user} u ON u.id = ctx.instanceid AND ctx.contextlevel = :contextuser
                          JOIN {enrol_arlo_contact} eac ON eac.userid = u.id
                         WHERE ctx.id {$contextsql} AND u.id = :userid";
                $params = [
                    'contextuser' => CONTEXT_USER,
                    'userid'      => $user->id
                ];
                $params += $contextparams;
                $rs = $DB->get_recordset_sql($sql, $params);
                foreach ($rs as $contact) {
                    $data = (object) [
                        'userid'        => $contact->userid,
                        'sourceid'      => $contact->sourceid,
                        'sourceguid'    => $contact->sourceguid,
                        'firstname'     => $contact->firstname,
                        'lastname'      => $contact->lastname,
                        'email'         => $contact->email,
                        'codeprimary'   => $contact->codeprimary,
                        'phonework'     => $contact->phonework,
                        'phonemobile'   => $contact->phonemobile
                    ];
                    writer::with_context($context)->export_data([
                            get_string('pluginname', 'enrol_arlo'),
                            get_string('metadata:enrol_arlo_contact', 'enrol_arlo')
                        ],
                        $data
                    );
                }
                $rs->close();
                // Email communications.
                $rs = $DB->get_recordset('enrol_arlo_emailqueue', ['userid' => $user->id]);
                foreach ($rs as $email) {
                    $data = (object) [
                        'area'          => $email->area,
                        'instanceid'    => $email->instanceid,
                        'userid'        => $email->userid,
                        'type'          => $email->type,
                        'status'        => $email->status,
                        'extra'         => $email->extra];
                    writer::with_context($context)->export_data([
                            get_string('pluginname', 'enrol_arlo'),
                            get_string('communications', 'enrol_arlo')
                        ],
                        $data
                    );
                }
                $rs->close();
            }
            if ($context instanceof context_course) {
                // Registration information.
                $subcontext = \core_enrol\privacy\provider::get_subcontext(
                    [
                        get_string('pluginname', 'enrol_arlo'),
                        get_string('metadata:enrol_arlo_registration', 'enrol_arlo')
                    ]
                );
                $sql = "SELECT ear.*
                          FROM {context} ctx
                          JOIN {enrol} e ON e.courseid = ctx.instanceid AND ctx.contextlevel = :contextcourse
                          JOIN {enrol_arlo_registration} ear ON ear.enrolid = e.id
                          JOIN {enrol_arlo_contact} eac ON eac.userid = ear.userid
                          JOIN {user} u ON u.id = eac.userid
                         WHERE ctx.id {$contextsql} AND u.id = :userid";
                $params = [
                    'contextcourse' => CONTEXT_COURSE,
                    'userid'        => $user->id
                ];
                $params += $contextparams;
                $rs = $DB->get_recordset_sql($sql, $params);
                foreach ($rs as $registration) {
                    $data = (object) [
                        'enrolid'           => $registration->enrolid,
                        'userid'            => $registration->userid,
                        'sourceid'          => $registration->sourceid,
                        'sourceguid'        => $registration->sourceguid,
                        'grade'             => $registration->grade,
                        'outcome'           => $registration->outcome,
                        'lastactivity'      => $registration->lastactivity,
                        'progressstatus'    => $registration->progressstatus,
                        'progresspercent'   => $registration->progresspercent,
                        'sourcecontactid'   => $registration->sourcecontactid,
                        'sourcecontactguid' => $registration->sourcecontactguid
                    ];
                    writer::with_context($context)->export_data($subcontext, $data);
                }
                $rs->close();
            }
        }
    }
    
    /**
     * Delete all user data which matches the specified context.
     *
     * @param context $context
     * @throws \dml_exception
     */
    public static function delete_data_for_all_users_in_context(context $context) {
        global $CFG, $DB;
        require_once($CFG->libdir . '/enrollib.php');
        if (empty($context)) {
            return;
        }
        if ($context instanceof context_course) {
            $rs = $DB->get_recordset('enrol', ['enrol' => 'arlo', 'courseid' => $context->instanceid]);
            foreach ($rs as $instance) {
                // Disable enrolment instance.
                $DB->set_field('enrol', 'status', ENROL_INSTANCE_DISABLED, ['id' => $instance->id]);
                // Delete associated registrations.
                $DB->delete_records('enrol_arlo_registration', ['enrolid' => $instance->id]);
                // Delete email queue information.
                $DB->delete_records('enrol_arlo_emailqueue', ['area' => 'site', 'instanceid' => $instance->id]);
            }
            // Delete all the associated groups.
            \core_group\privacy\provider::delete_groups_for_all_users($context, 'enrol_arlo');
        }
        if ($context instanceof context_user) {
            // Delete contact association.
            $DB->delete_records('enrol_arlo_contact', ['userid' => $context->instanceid]);
            // Delete email queue information.
            $DB->delete_records('enrol_arlo_emailqueue', ['area' => 'enrolment', 'userid' => $context->instanceid]);
        }
    }
    
    /**
     * Delete all user data for the specified user, in the specified contexts.
     *
     * @param approved_contextlist $contextlist
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        global $DB;
        if (empty($contextlist->count())) {
            return;
        }
        $courseids = [];
        $user = $contextlist->get_user();
        $contexts = $contextlist->get_contexts();
        foreach ($contexts as $context) {
            if ($context instanceof context_course) {
                $courseids[] = $context->instanceid;
            }
        }
        list($sql, $params) = $DB->get_in_or_equal($courseids, SQL_PARAMS_NAMED);
        $enrolids = $DB->get_fieldset_select(
            'enrol',
            'id',
            "enrol = 'arlo' AND courseid $sql",
            $params
        );
        if (!empty($enrolids)) {
            list($sql, $params) = $DB->get_in_or_equal($enrolids, SQL_PARAMS_NAMED);
            $params = array_merge($params, ['userid' => $user->id]);
            // Delete associated registrations.
            $DB->delete_records_select(
                'enrol_arlo_registration',
                "enrolid $sql AND userid = :userid",
                $params
            );
        }
        // Delete contact association.
        $DB->delete_records('enrol_arlo_contact', ['userid' => $user->id]);
        // Delete email queue information.
        $DB->delete_records('enrol_arlo_emailqueue', ['userid' => $user->id]);
        // Delete all the associated groups.
        \core_group\privacy\provider::delete_groups_for_user($contextlist, 'enrol_arlo');
    }
    
    /**
     * Delete multiple users within a single context.
     *
     * @param approved_userlist $userlist
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public static function delete_data_for_users(approved_userlist $userlist) {
        global $DB;
        $context = $userlist->get_context();
        $userids = $userlist->get_userids();
        if ($context instanceof context_course) {
            $enrolids = $DB->get_fieldset_select(
                'enrol',
                'id',
                "enrol = 'arlo' AND courseid = :courseid",
                ['courseid' => $context->instanceid]
            );
            if ($enrolids) {
                list($enrolsql, $enrolparams) = $DB->get_in_or_equal($enrolids, SQL_PARAMS_NAMED);
                list($usersql, $userparams) = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED);
                $params = $enrolparams + $userparams;
                // Delete associated registrations.
                $DB->delete_records_select(
                    'enrol_arlo_registration',
                    "enrolid $enrolsql AND userid $usersql",
                    $params
                );
                // Delete email queue information.
                $DB->delete_records_select(
                    'enrol_arlo_emailqueue',
                    "area = 'enrolment' AND instanceid $enrolsql AND userid $usersql",
                    $params
                );
            }
            \core_group\privacy\provider::delete_groups_for_users($userlist, 'enrol_arlo');
        }
    }
}
