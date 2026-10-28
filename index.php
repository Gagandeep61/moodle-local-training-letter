<?php
require_once(__DIR__ . '/../../config.php');

require_login();

$context = context_system::instance();

$PAGE->set_url(new moodle_url('/local/training_letter/index.php'));
$PAGE->set_context($context);
$PAGE->set_title(get_string('pluginname', 'local_training_letter'));
$PAGE->set_heading(get_string('pluginname', 'local_training_letter'));

$cansubmit = has_capability('local/training_letter:submitrequest', $context);
$canmanage = has_capability('local/training_letter:managerequests', $context);

echo $OUTPUT->header();
echo html_writer::tag('p', get_string('testcansubmit', 'local_training_letter',
    $cansubmit ? get_string('yes') : get_string('no')));
echo html_writer::tag('p', get_string('testcanmanage', 'local_training_letter',
    $canmanage ? get_string('yes') : get_string('no')));
echo $OUTPUT->footer();