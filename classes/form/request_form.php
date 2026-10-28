<?php
/**
 * Training request form.
 *
 * @package    local_training_letter
 * @copyright  2026 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_training_letter\form;

use local_training_letter\helper;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/formslib.php');

/**
 * Form where a student enters training details.
 */
class request_form extends \moodleform {

    /**
     * Builds the form fields.
     */
    public function definition() {
        $mform = $this->_form;
        $pn = 'local_training_letter';

        // Section 1: student details.
        $mform->addElement('header', 'studentheader', get_string('studentdetails', $pn));

        $mform->addElement('text', 'fullname', get_string('fullname', $pn), ['size' => 40]);
        $mform->setType('fullname', PARAM_TEXT);
        $mform->addRule('fullname', get_string('required'), 'required', null, 'client');
        $mform->addRule('fullname', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');

        $mform->addElement('text', 'rollno', get_string('rollno', $pn), ['size' => 15]);
        $mform->setType('rollno', PARAM_ALPHANUM);
        $mform->addRule('rollno', get_string('required'), 'required', null, 'client');
        $mform->addRule('rollno', get_string('err_rollno', $pn), 'regex', '/^[0-9]{5,10}$/', 'client');

        $mform->addElement('text', 'branch', get_string('branch', $pn), ['size' => 20]);
        $mform->setType('branch', PARAM_TEXT);
        $mform->addRule('branch', get_string('required'), 'required', null, 'client');
        $mform->addRule('branch', get_string('maximumchars', '', 100), 'maxlength', 100, 'client');

        $semesters = ['' => get_string('choosedots')];
        foreach (range(1, 8) as $number) {
            $semesters[$number] = $number;
        }
        $mform->addElement('select', 'semester', get_string('semester', $pn), $semesters);
        $mform->setType('semester', PARAM_INT);
        $mform->addRule('semester', get_string('required'), 'required', null, 'client');

        $mform->addElement('text', 'studentphone', get_string('studentphone', $pn), ['size' => 15]);
        $mform->setType('studentphone', PARAM_ALPHANUM);
        $mform->addRule('studentphone', get_string('required'), 'required', null, 'client');
        $mform->addRule('studentphone', get_string('err_studentphone', $pn), 'regex', '/^[6-9][0-9]{9}$/', 'client');

        // Section 2: company details.
        $mform->addElement('header', 'companyheader', get_string('companydetails', $pn));

        $mform->addElement('text', 'companyname', get_string('companyname', $pn), ['size' => 40]);
        $mform->setType('companyname', PARAM_TEXT);
        $mform->addRule('companyname', get_string('required'), 'required', null, 'client');
        $mform->addRule('companyname', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');

        $mform->addElement('textarea', 'companyaddress', get_string('companyaddress', $pn),
            ['rows' => 3, 'cols' => 40]);
        $mform->setType('companyaddress', PARAM_TEXT);
        $mform->addRule('companyaddress', get_string('required'), 'required', null, 'client');

        $mform->addElement('text', 'contactperson', get_string('contactperson', $pn), ['size' => 40]);
        $mform->setType('contactperson', PARAM_TEXT);
        $mform->addRule('contactperson', get_string('required'), 'required', null, 'client');
        $mform->addRule('contactperson', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');

        $mform->addElement('text', 'companyphone', get_string('companyphone', $pn), ['size' => 15]);
        $mform->setType('companyphone', PARAM_ALPHANUM);
        $mform->addRule('companyphone', get_string('required'), 'required', null, 'client');
        $mform->addRule('companyphone', get_string('err_companyphone', $pn), 'regex', '/^[0-9]{10,12}$/', 'client');

        // Section 3: training details.
        $mform->addElement('header', 'trainingheader', get_string('trainingdetails', $pn));

        $mform->addElement('select', 'course', get_string('course', $pn),
            ['' => get_string('choosedots')] + helper::get_courses());
        $mform->setType('course', PARAM_ALPHA);
        $mform->addRule('course', get_string('required'), 'required', null, 'client');

        $mform->addElement('select', 'trainingtype', get_string('trainingtype', $pn),
            ['' => get_string('choosedots')] + helper::get_training_types());
        $mform->setType('trainingtype', PARAM_ALPHA);
        $mform->addRule('trainingtype', get_string('required'), 'required', null, 'client');

        $mform->addElement('select', 'duration', get_string('duration', $pn),
            ['' => get_string('choosedots')] + helper::get_durations());
        $mform->setType('duration', PARAM_ALPHANUM);
        $mform->addRule('duration', get_string('required'), 'required', null, 'client');

        $mform->addElement('date_selector', 'joiningdate', get_string('joiningdate', $pn));

        $this->add_action_buttons(false, get_string('submitrequest', $pn));
    }

    /**
     * Server-side checks. Runs after the browser checks, and cannot be skipped.
     *
     * @param array $data submitted values
     * @param array $files uploaded files (none here)
     * @return array field name => error message
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        $pn = 'local_training_letter';

        // Text fields must not be only spaces.
        $textfields = ['fullname', 'branch', 'companyname', 'companyaddress', 'contactperson'];
        foreach ($textfields as $field) {
            if (trim($data[$field]) === '') {
                $errors[$field] = get_string('required');
            }
        }

        // Person names: letters, spaces, dot, hyphen, apostrophe.
        foreach (['fullname', 'contactperson'] as $field) {
            if (!isset($errors[$field]) && !preg_match("/^[\p{L} .'-]+$/u", trim($data[$field]))) {
                $errors[$field] = get_string('err_name', $pn);
            }
        }

        // Numbers.
        if (!preg_match('/^[0-9]{5,10}$/', $data['rollno'])) {
            $errors['rollno'] = get_string('err_rollno', $pn);
        }
        if (!preg_match('/^[6-9][0-9]{9}$/', $data['studentphone'])) {
            $errors['studentphone'] = get_string('err_studentphone', $pn);
        }
        if (!preg_match('/^[0-9]{10,12}$/', $data['companyphone'])) {
            $errors['companyphone'] = get_string('err_companyphone', $pn);
        }

        // Dropdown values must be real options (a faked request could send anything).
        if ((int) $data['semester'] < 1 || (int) $data['semester'] > 8) {
            $errors['semester'] = get_string('err_invalidchoice', $pn);
        }
        if (!array_key_exists($data['course'], helper::get_courses())) {
            $errors['course'] = get_string('err_invalidchoice', $pn);
        }
        if (!array_key_exists($data['trainingtype'], helper::get_training_types())) {
            $errors['trainingtype'] = get_string('err_invalidchoice', $pn);
        }
        if (!array_key_exists($data['duration'], helper::get_durations())) {
            $errors['duration'] = get_string('err_invalidchoice', $pn);
        }

        // Joining date must be today or later.
        if ($data['joiningdate'] < usergetmidnight(time())) {
            $errors['joiningdate'] = get_string('err_joiningdate', $pn);
        }

        return $errors;
    }
}