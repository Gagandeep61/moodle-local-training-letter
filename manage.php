<?php
/**
 * Coordinator page: list training requests.
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
$allowed = ['pending', 'approved', 'rejected', 'all'];
$filter = optional_param('status', 'pending', PARAM_ALPHA);
if (!in_array($filter, $allowed)) {
    $filter = 'pending';
}

$PAGE->set_url(new moodle_url('/local/training_letter/manage.php', ['status' => $filter]));
$PAGE->set_context($context);
$PAGE->set_title(get_string('managerequests', $pn));
$PAGE->set_heading(get_string('managerequests', $pn));

echo $OUTPUT->header();

// Filter links: the current choice is bold, the others are links.
$links = [];
foreach ($allowed as $option) {
    $label = ($option === 'all') ? get_string('filter_all', $pn) : helper::get_status_label($option);
    if ($option === $filter) {
        $links[] = html_writer::tag('strong', $label);
    } else {
        $links[] = html_writer::link(
            new moodle_url('/local/training_letter/manage.php', ['status' => $option]), $label);
    }
}
echo html_writer::tag('p', get_string('showstatus', $pn) . ' ' . implode(' | ', $links));

// Pending: oldest first (fair queue). Others: newest first.
$conditions = ($filter === 'all') ? [] : ['status' => $filter];
$order = ($filter === 'pending') ? 'timecreated ASC' : 'timecreated DESC';
$requests = $DB->get_records('local_training_letter', $conditions, $order);

if (!$requests) {
    echo $OUTPUT->notification(get_string('nomatching', $pn),
        \core\output\notification::NOTIFY_INFO);
} else {
    $courses = helper::get_courses();
    $durations = helper::get_durations();

    $table = new html_table();
    $table->head = [
        get_string('submitted', $pn),
        get_string('student', $pn),
        get_string('companyname', $pn),
        get_string('course', $pn),
        get_string('duration', $pn),
        get_string('status', $pn),
        get_string('action', $pn),
    ];
    foreach ($requests as $request) {
        $reviewlink = html_writer::link(
            new moodle_url('/local/training_letter/review.php', ['id' => $request->id]),
            get_string('review', $pn));
        $table->data[] = [
            userdate($request->timecreated, get_string('strftimedatefullshort', 'langconfig')),
            s($request->fullname) . ' (' . s($request->rollno) . ')',
            s($request->companyname),
            s($courses[$request->course] ?? $request->course),
            s($durations[$request->duration] ?? $request->duration),
            helper::get_status_label($request->status),
            $reviewlink,
        ];
    }
    echo html_writer::table($table);
}

echo $OUTPUT->footer();
