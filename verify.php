<?php
/**
 * Public page where anyone can check that a letter is genuine.
 *
 * @package    local_training_letter
 * @copyright  2026 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

$pn = 'local_training_letter';
$ref = optional_param('ref', '', PARAM_RAW_TRIMMED);

$PAGE->set_url(new moodle_url('/local/training_letter/verify.php'));
$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('base');
$PAGE->set_title(get_string('verifytitle', $pn));
$PAGE->set_heading(get_string('verifytitle', $pn));

echo $OUTPUT->header();

// Small search box.
echo html_writer::start_tag('form', ['method' => 'get', 'action' => $PAGE->url->out(false)]);
echo html_writer::empty_tag('input', [
    'type' => 'text', 'name' => 'ref', 'value' => $ref, 'size' => 30,
    'placeholder' => 'GNDEC/2026/0001', 'class' => 'form-control d-inline-block w-auto',
]);
echo html_writer::empty_tag('input', [
    'type' => 'submit', 'value' => get_string('verifycheck', $pn), 'class' => 'btn btn-primary ms-2',
]);
echo html_writer::end_tag('form');
echo html_writer::empty_tag('br');

if ($ref !== '') {
    // Only approved letters can ever be genuine.
    $request = $DB->get_record('local_training_letter', ['refno' => $ref, 'status' => 'approved']);

    if ($request) {
        echo $OUTPUT->notification(get_string('verifyvalid', $pn),
            \core\output\notification::NOTIFY_SUCCESS);

        $table = new html_table();
        $table->data = [
            [get_string('refno', $pn), s($request->refno)],
            [get_string('student', $pn), s($request->fullname)],
            [get_string('companyname', $pn), s($request->companyname)],
            [get_string('verifydate', $pn), date('d-m-Y', $request->timereviewed)],
        ];
        echo html_writer::table($table);
    } else {
        echo $OUTPUT->notification(get_string('verifynotfound', $pn),
            \core\output\notification::NOTIFY_ERROR);
    }
}

echo $OUTPUT->footer();
