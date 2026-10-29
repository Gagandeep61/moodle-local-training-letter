<?php
/**
 * Student page: submit a training request and see own requests.
 *
 * @package    local_training_letter
 * @copyright  2026 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

require_login();

$context = context_system::instance();
require_capability('local/training_letter:submitrequest', $context);

$PAGE->set_url(new moodle_url('/local/training_letter/index.php'));
$PAGE->set_context($context);
$PAGE->set_title(get_string('pluginname', 'local_training_letter'));
$PAGE->set_heading(get_string('pluginname', 'local_training_letter'));

$mform = new \local_training_letter\form\request_form();

if ($data = $mform->get_data()) {
    // Form passed every check. Save it.
    $record = new stdClass();
    $record->userid = $USER->id;
    $record->fullname = trim($data->fullname);
    $record->rollno = $data->rollno;
    $record->branch = trim($data->branch);
    $record->semester = $data->semester;
    $record->studentphone = $data->studentphone;
    $record->course = $data->course;
    $record->companyname = trim($data->companyname);
    $record->companyaddress = trim($data->companyaddress);
    $record->contactperson = trim($data->contactperson);
    $record->companyphone = $data->companyphone;
    $record->trainingtype = $data->trainingtype;
    $record->duration = $data->duration;
    $record->joiningdate = $data->joiningdate;
    $record->status = 'pending';
    $record->timecreated = time();
    $record->timemodified = $record->timecreated;

    $DB->insert_record('local_training_letter', $record);

    redirect(
        $PAGE->url,
        get_string('requestsaved', 'local_training_letter'),
        null,
        \core\output\notification::NOTIFY_SUCCESS
    );
}

// Pre-fill from the student's Moodle profile. Typed values win if the form is re-shown.
$mform->set_data((object) [
    'fullname' => fullname($USER),
    'rollno' => $USER->idnumber,
    'branch' => $USER->department,
    'studentphone' => $USER->phone2,
]);

echo $OUTPUT->header();

if (has_capability('local/training_letter:managerequests', $context)) {
    echo $OUTPUT->single_button(
        new moodle_url('/local/training_letter/manage.php'),
        get_string('managerequests', 'local_training_letter'),
        'get'
    );
}

echo $OUTPUT->heading(get_string('newrequest', 'local_training_letter'), 3);
$mform->display();

echo $OUTPUT->heading(get_string('myrequests', 'local_training_letter'), 3);

$requests = $DB->get_records('local_training_letter', ['userid' => $USER->id], 'timecreated DESC');

if (!$requests) {
    echo $OUTPUT->notification(
        get_string('norequests', 'local_training_letter'),
        \core\output\notification::NOTIFY_INFO
    );
} else {
    $courses = \local_training_letter\helper::get_courses();
    $durations = \local_training_letter\helper::get_durations();

    $table = new html_table();
    $table->head = [
        get_string('submitted', 'local_training_letter'),
        get_string('companyname', 'local_training_letter'),
        get_string('course', 'local_training_letter'),
        get_string('duration', 'local_training_letter'),
        get_string('status', 'local_training_letter'),
        get_string('remarks', 'local_training_letter'),
        get_string('letter', 'local_training_letter'),
    ];

    foreach ($requests as $request) {
        $lettercell = '';
        if ($request->status === 'approved') {
            $previewurl = new moodle_url('/local/training_letter/letter.php', ['id' => $request->id]);
            $downloadurl = new moodle_url('/local/training_letter/letter.php',
                ['id' => $request->id, 'download' => 1]);
            $lettercell = html_writer::link($previewurl, get_string('preview', 'local_training_letter'),
                    ['target' => '_blank']) . ' | ' .
                html_writer::link($downloadurl, get_string('download', 'local_training_letter'));
        }
        $table->data[] = [
            userdate($request->timecreated, get_string('strftimedatefullshort', 'langconfig')),
            s($request->companyname),
            s($courses[$request->course] ?? $request->course),
            s($durations[$request->duration] ?? $request->duration),
            \local_training_letter\helper::get_status_label($request->status),
            s($request->remarks ?? ''),
            $lettercell,
        ];
    }
    echo html_writer::table($table);
}

echo $OUTPUT->footer();