<?php
/**
 * Shared option lists for the Training Request Letter plugin.
 *
 * @package    local_training_letter
 * @copyright  2026 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_training_letter;

/**
 * Helper functions used by the form, the pages and the letter.
 */
class helper {

    /**
     * Courses, in the same order as the checkboxes on the real letter.
     *
     * @return array code => display name
     */
    public static function get_courses(): array {
        return [
            'btech' => 'B.Tech',
            'mca' => 'MCA',
            'mba' => 'MBA',
            'bca' => 'BCA',
            'bba' => 'BBA',
            'bcom' => 'B.Com',
            'barch' => 'B.Arch',
        ];
    }

    /**
     * Training durations.
     *
     * @return array code => display name
     */
    public static function get_durations(): array {
        return [
            '4weeks' => get_string('duration_4weeks', 'local_training_letter'),
            '6weeks' => get_string('duration_6weeks', 'local_training_letter'),
            '6months' => get_string('duration_6months', 'local_training_letter'),
        ];
    }

    /**
     * Training types.
     *
     * @return array code => display name
     */
    public static function get_training_types(): array {
        return [
            'software' => get_string('type_software', 'local_training_letter'),
            'industrial' => get_string('type_industrial', 'local_training_letter'),
        ];
    }

    /**
     * Readable label for a status code.
     *
     * @param string $status pending, approved or rejected
     * @return string
     */
    public static function get_status_label(string $status): string {
        return get_string('status_' . $status, 'local_training_letter');
    }

    /**
     * Hides most letters of each name part, e.g. "training_letter" becomes "G******* S****".
     *
     * @param string $name full name
     * @return string masked name
     */
    public static function mask_name(string $name): string {
        $masked = [];
        foreach (preg_split('/\s+/u', trim($name)) as $part) {
            $masked[] = \core_text::substr($part, 0, 1) . str_repeat('*', max(\core_text::strlen($part) - 1, 0));
        }
        return implode(' ', $masked);
    }
}
