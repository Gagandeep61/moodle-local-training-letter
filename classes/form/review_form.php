<?php
/**
 * Coordinator decision form: remarks plus Approve / Reject buttons.
 *
 * @package    local_training_letter
 * @copyright  2026 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_training_letter\form;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/formslib.php');

/**
 * Form where the coordinator approves or rejects one request.
 */
class review_form extends \moodleform {

    /**
     * Builds the form fields.
     */
    public function definition() {
        $mform = $this->_form;
        $pn = 'local_training_letter';

        $mform->addElement('hidden', 'id', $this->_customdata['id']);
        $mform->setType('id', PARAM_INT);

        $mform->addElement('textarea', 'remarks', get_string('remarksoptional', $pn),
            ['rows' => 4, 'cols' => 50]);
        $mform->setType('remarks', PARAM_TEXT);
        $mform->addRule('remarks', get_string('maximumchars', '', 500), 'maxlength', 500, 'client');

        $buttons = [];
        $buttons[] = $mform->createElement('submit', 'approve', get_string('approve', $pn));
        $buttons[] = $mform->createElement('submit', 'reject', get_string('reject', $pn));
        $buttons[] = $mform->createElement('cancel');
        $mform->addGroup($buttons, 'buttonar', '', [' '], false);
    }
}
