<?php
/**
 * Privacy provider for the Training Request Letter plugin.
 *
 * @package    local_training_letter
 * @copyright  2026 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_training_letter\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\transform;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;

defined('MOODLE_INTERNAL') || die();

/**
 * Says what personal data the plugin keeps, and how to export or delete it.
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\core_userlist_provider,
    \core_privacy\local\request\plugin\provider {

    /**
     * Lists the data stored in the plugin table.
     *
     * @param collection $collection collection to fill
     * @return collection
     */
    public static function get_metadata(collection $collection): collection {
        $fields = [
            'userid', 'fullname', 'rollno', 'branch', 'semester', 'studentphone', 'course',
            'companyname', 'companyaddress', 'contactperson', 'companyphone', 'trainingtype',
            'duration', 'joiningdate', 'status', 'remarks', 'refno', 'reviewedby',
            'timereviewed', 'timecreated', 'timemodified',
        ];
        $map = [];
        foreach ($fields as $field) {
            $map[$field] = 'privacy:metadata:local_training_letter:' . $field;
        }
        $collection->add_database_table('local_training_letter', $map,
            'privacy:metadata:local_training_letter');
        return $collection;
    }

    /**
     * Finds where this user has data. All data sits in the system context.
     *
     * @param int $userid user id
     * @return contextlist
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        $contextlist = new contextlist();
        $sql = "SELECT ctx.id
                  FROM {context} ctx
                 WHERE ctx.contextlevel = :contextlevel
                   AND EXISTS (SELECT 1
                                 FROM {local_training_letter} tl
                                WHERE tl.userid = :userid1 OR tl.reviewedby = :userid2)";
        $contextlist->add_from_sql($sql, [
            'contextlevel' => CONTEXT_SYSTEM,
            'userid1' => $userid,
            'userid2' => $userid,
        ]);
        return $contextlist;
    }

    /**
     * Exports the user's own requests.
     *
     * @param approved_contextlist $contextlist approved contexts
     */
    public static function export_user_data(approved_contextlist $contextlist): void {
        global $DB;

        if ($contextlist->count() === 0) {
            return;
        }
        $records = $DB->get_records('local_training_letter',
            ['userid' => $contextlist->get_user()->id], 'timecreated ASC');
        if (!$records) {
            return;
        }

        $requests = [];
        foreach ($records as $record) {
            $requests[] = (object) [
                'fullname' => $record->fullname,
                'rollno' => $record->rollno,
                'branch' => $record->branch,
                'semester' => $record->semester,
                'studentphone' => $record->studentphone,
                'course' => $record->course,
                'companyname' => $record->companyname,
                'companyaddress' => $record->companyaddress,
                'contactperson' => $record->contactperson,
                'companyphone' => $record->companyphone,
                'trainingtype' => $record->trainingtype,
                'duration' => $record->duration,
                'joiningdate' => transform::datetime($record->joiningdate),
                'status' => $record->status,
                'remarks' => $record->remarks,
                'refno' => $record->refno,
                'timecreated' => transform::datetime($record->timecreated),
            ];
        }

        writer::with_context(\context_system::instance())->export_data(
            [get_string('pluginname', 'local_training_letter')],
            (object) ['requests' => $requests]
        );
    }

    /**
     * Deletes all data of all users in a context.
     *
     * @param \context $context context
     */
    public static function delete_data_for_all_users_in_context(\context $context): void {
        global $DB;

        if ($context->contextlevel == CONTEXT_SYSTEM) {
            $DB->delete_records('local_training_letter');
        }
    }

    /**
     * Deletes one user's requests. Decisions they made as coordinator stay, but lose their name.
     *
     * @param approved_contextlist $contextlist approved contexts
     */
    public static function delete_data_for_user(approved_contextlist $contextlist): void {
        global $DB;

        if ($contextlist->count() === 0) {
            return;
        }
        $userid = $contextlist->get_user()->id;
        $DB->delete_records('local_training_letter', ['userid' => $userid]);
        $DB->set_field('local_training_letter', 'reviewedby', null, ['reviewedby' => $userid]);
    }

    /**
     * Lists users who have data in a context.
     *
     * @param userlist $userlist user list to fill
     */
    public static function get_users_in_context(userlist $userlist): void {
        if ($userlist->get_context()->contextlevel != CONTEXT_SYSTEM) {
            return;
        }
        $userlist->add_from_sql('userid', 'SELECT userid FROM {local_training_letter}', []);
        $userlist->add_from_sql('reviewedby',
            'SELECT reviewedby FROM {local_training_letter} WHERE reviewedby IS NOT NULL', []);
    }

    /**
     * Deletes data of several users in a context.
     *
     * @param approved_userlist $userlist approved users
     */
    public static function delete_data_for_users(approved_userlist $userlist): void {
        global $DB;

        if ($userlist->get_context()->contextlevel != CONTEXT_SYSTEM) {
            return;
        }
        $userids = $userlist->get_userids();
        if (empty($userids)) {
            return;
        }
        [$insql, $inparams] = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED);
        $DB->delete_records_select('local_training_letter', "userid $insql", $inparams);

        [$insql2, $inparams2] = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED);
        $DB->set_field_select('local_training_letter', 'reviewedby', null,
            "reviewedby $insql2", $inparams2);
    }
}
