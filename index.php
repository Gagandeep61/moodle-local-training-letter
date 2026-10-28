<?php
require_once(__DIR__ . '/../../config.php');

require_login();

$context = context_system::instance();

$PAGE->set_url(new moodle_url('/local/training_letter/index.php'));
$PAGE->set_context($context);
$PAGE->set_title(get_string('pluginname', 'local_training_letter'));
$PAGE->set_heading(get_string('pluginname', 'local_training_letter'));

echo $OUTPUT->header();
echo $OUTPUT->notification(
    get_string('skeletonworks', 'local_training_letter'),
    \core\output\notification::NOTIFY_SUCCESS
);
echo $OUTPUT->footer();