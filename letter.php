<?php
/**
 * Preview or download an approved training request letter.
 *
 * @package    local_training_letter
 * @copyright  2026 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

require_login();

$id = required_param('id', PARAM_INT);
$download = optional_param('download', 0, PARAM_BOOL);

$context = context_system::instance();
$request = $DB->get_record('local_training_letter', ['id' => $id], '*', MUST_EXIST);

// Gate 1: only the student who owns the request, or a coordinator.
$ismanager = has_capability('local/training_letter:managerequests', $context);
$isowner = ((int) $request->userid === (int) $USER->id)
    && has_capability('local/training_letter:submitrequest', $context);
if (!$ismanager && !$isowner) {
    throw new moodle_exception('nopermissiontoshow', 'error');
}

// Gate 2: only after approval.
if ($request->status !== 'approved') {
    throw new moodle_exception('letternotapproved', 'local_training_letter');
}

$pdf = \local_training_letter\letter_generator::build_pdf($request);
$filename = str_replace('/', '_', $request->refno) . '.pdf';
$pdf->Output($filename, $download ? 'D' : 'I');
die();
