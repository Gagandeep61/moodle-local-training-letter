<?php
/**
 * Coordinator page: view one request and approve or reject it.
 *
 * @package    local_training_letter
 * @copyright  2026 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

use local_training_letter\helper;

require_login();

$context = context_system::instance();
require_capability('local/training_letter:managerequests', $context);

$pn = 'local_training_letter';
$id = required_param('id', PARAM_INT);
$request = $DB->get_record('local_training_letter', ['id' => $id], '*', MUST_EXIST);
$listurl = new moodle_url('/local/training_letter/manage.php');

$PAGE->set_url(new moodle_url('/local/training_letter/review.php', ['id' => $id]));
$PAGE->set_context($context);
$PAGE->set_title(get_string('reviewrequest', $pn));
$PAGE->set_heading(get_string('reviewrequest', $pn));

$mform = new \local_training_letter\form\review_form(null, ['id' => $id]);

if ($mform->is_cancelled()) {
    redirect($listurl);
}

if ($data = $mform->get_data()) {
    // Read the row again: another coordinator may have decided a moment ago.
    $current = $DB->get_record('local_training_letter', ['id' => $id], '*', MUST_EXIST);
    if ($current->status !== 'pending') {
        redirect($listurl, get_string('alreadyreviewed', $pn), null,
            \core\output\notification::NOTIFY_WARNING);
    }

    $update = new stdClass();
    $update->id = $id;
    $update->remarks = trim($data->remarks);
    $update->reviewedby = $USER->id;
    $update->timereviewed = time();
    $update->timemodified = $update->timereviewed;

    if (!empty($data->approve)) {
        $update->status = 'approved';
        // Reference number: year + request id padded to 4 digits. The id is unique, so the ref is too.
        $update->refno = 'GNDEC/' . date('Y') . '/' . str_pad($id, 4, '0', STR_PAD_LEFT);
        $message = get_string('approved_msg', $pn, $update->refno);
    } else {
        $update->status = 'rejected';
        $message = get_string('rejected_msg', $pn);
    }

    $DB->update_record('local_training_letter', $update);
    redirect($listurl, $message, null, \core\output\notification::NOTIFY_SUCCESS);
}

$courses = helper::get_courses();
$durations = helper::get_durations();
$types = helper::get_training_types();
$dateformat = get_string('strftimedatefullshort', 'langconfig');

$rows = [
    [get_string('fullname', $pn), s($request->fullname)],
    [get_string('rollno', $pn), s($request->rollno)],
    [get_string('branch', $pn), s($request->branch)],
    [get_string('semester', $pn), s($request->semester)],
    [get_string('studentphone', $pn), s($request->studentphone)],
    [get_string('companyname', $pn), s($request->companyname)],
    [get_string('companyaddress', $pn), nl2br(s($request->companyaddress))],
    [get_string('contactperson', $pn), s($request->contactperson)],
    [get_string('companyphone', $pn), s($request->companyphone)],
    [get_string('course', $pn), s($courses[$request->course] ?? $request->course)],
    [get_string('trainingtype', $pn), s($types[$request->trainingtype] ?? $request->trainingtype)],
    [get_string('duration', $pn), s($durations[$request->duration] ?? $request->duration)],
    [get_string('joiningdate', $pn), userdate($request->joiningdate, $dateformat)],
    [get_string('status', $pn), helper::get_status_label($request->status)],
];

if ($request->status !== 'pending') {
    $rows[] = [get_string('refno', $pn), s($request->refno ?? '-')];
    $rows[] = [get_string('remarks', $pn), s($request->remarks ?? '')];
    $rows[] = [get_string('reviewedon', $pn), userdate($request->timereviewed, $dateformat)];
}

$table = new html_table();
$table->data = $rows;

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('requestdetails', $pn), 3);
echo html_writer::table($table);

if ($request->status === 'pending') {
    $mform->display();
} else {
    if ($request->status === 'approved') {
        echo $OUTPUT->single_button(
            new moodle_url('/local/training_letter/letter.php', ['id' => $id]),
            get_string('preview', $pn), 'get');
        echo $OUTPUT->single_button(
            new moodle_url('/local/training_letter/letter.php', ['id' => $id, 'download' => 1]),
            get_string('download', $pn), 'get');
    }
    echo $OUTPUT->single_button($listurl, get_string('backtolist', $pn), 'get');
}

echo $OUTPUT->footer();
