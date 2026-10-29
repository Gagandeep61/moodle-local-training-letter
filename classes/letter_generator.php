<?php
/**
 * Builds the Training Request Letter as a PDF.
 *
 * @package    local_training_letter
 * @copyright  2026 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_training_letter;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/pdflib.php');

/**
 * Draws the official GNDEC training request letter for one approved request.
 */
class letter_generator {

    /** @var float Base font size of the letter, in points. Lower it if the letter spills to page 2. */
    const FONT_SIZE = 9.5;

    /**
     * Builds the letter.
     *
     * @param \stdClass $request approved row from local_training_letter
     * @return \pdf the finished PDF object (call Output() on it)
     */
    public static function build_pdf(\stdClass $request): \pdf {
        $pn = 'local_training_letter';
        $verifyurl = (new \moodle_url('/local/training_letter/verify.php',
            ['ref' => $request->refno]))->out(false);
        $date = date('d-m-Y', $request->timereviewed);
        $studentname = \core_text::strtoupper(trim($request->fullname));
        $gap = '&nbsp;&nbsp;&nbsp;&nbsp;';

        // Page setup: A4, no automatic header or footer.
        $pdf = new \pdf('P', 'mm', 'A4', true, 'UTF-8');
        $pdf->SetCreator('GNDEC Training and Placement Cell');
        $pdf->SetTitle(get_string('letter_title', $pn) . ' ' . $request->refno);
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetMargins(15, 12, 15);
        $pdf->SetAutoPageBreak(true, 12);
        $pdf->setListIndentWidth(5);
        $pdf->AddPage();

        // Letterhead (centred, text only).
        $pdf->SetFont('helvetica', '', 9);
        $head = '<div style="text-align:center;">'
            . '<span style="font-size:15pt;"><b>' . get_string('letter_college', $pn) . '</b></span><br />'
            . '<span style="font-size:10pt;"><b>' . get_string('letter_tnpcell', $pn) . '</b></span><br />'
            . '<span style="font-size:10pt;"><b>' . get_string('letter_govt', $pn) . '</b></span><br />'
            . '<span style="font-size:9pt;"><b>' . get_string('letter_autonomous', $pn) . '</b></span><br />'
            . '<span style="font-size:8.5pt;color:#1155cc;">' . get_string('letter_websites', $pn) . '</span>'
            . '</div>';
        $pdf->writeHTML($head, true, false, true, false, '');

        // Title.
        $pdf->Ln(2);
        $title = '<div style="text-align:center;font-family:times;font-size:17pt;"><b>'
            . get_string('letter_title', $pn) . '</b></div>';
        $pdf->writeHTML($title, true, false, true, false, '');
        $pdf->Ln(2);

        // Course and duration line, with ticked boxes like the real letter.
        $courseline = '<b>' . get_string('letter_course', $pn) . '</b> ';
        foreach (helper::get_courses() as $code => $name) {
            $courseline .= self::tick($code === $request->course) . '<b>' . $name . '</b> ';
        }
        foreach (self::get_letter_durations() as $code => $name) {
            $courseline .= self::tick($code === $request->duration) . '<b>' . $name . '</b> ';
        }

        // Body text with the five requirement bullets.
        $body = get_string('letter_salutation', $pn) . '<br />'
            . get_string('letter_greeting', $pn) . '<br />'
            . get_string('letter_para1', $pn) . '<br />'
            . get_string('letter_para2', $pn) . '<br />'
            . get_string('letter_para3', $pn)
            . '<ul>';
        for ($i = 1; $i <= 5; $i++) {
            $body .= '<li>' . get_string('letter_bullet' . $i, $pn) . '</li>';
        }
        $body .= '</ul>' . get_string('letter_expect', $pn);

        // The big bordered box: one table, one row per block.
        $html = '<table border="1" cellpadding="4" cellspacing="0" width="100%">'
            . '<tr><td width="62%"><b>' . get_string('letter_refno', $pn) . ' ' . s($request->refno) . '</b></td>'
            . '<td width="38%"><b>' . get_string('letter_dated', $pn) . ' ' . $date . '</b></td></tr>'
            . '<tr><td colspan="2"><b>' . get_string('letter_companyname', $pn) . ' '
            . s($request->companyname) . '</b></td></tr>'
            . '<tr><td colspan="2"><b>' . get_string('letter_companyaddress', $pn) . ' '
            . nl2br(s($request->companyaddress)) . '</b></td></tr>'
            . '<tr><td width="62%"><b>' . get_string('letter_contactperson', $pn) . ' '
            . s($request->contactperson) . '</b></td>'
            . '<td width="38%"><b>' . get_string('letter_phonenumber', $pn) . ' '
            . s($request->companyphone) . '</b></td></tr>'
            . '<tr><td colspan="2">' . $courseline . '</td></tr>'
            . '<tr><td colspan="2" style="text-align:justify;">' . $body . '</td></tr>'
            . '<tr><td colspan="2">' . get_string('letter_recommend', $pn) . '<br />'
            . '<b>' . get_string('letter_student', $pn) . ' ' . s($studentname) . $gap
            . get_string('letter_classroll', $pn) . ' ' . s($request->rollno) . $gap
            . get_string('letter_branch', $pn) . ' ' . s($request->branch) . $gap
            . get_string('letter_phoneno', $pn) . ' ' . s($request->studentphone) . '</b><br />'
            . '<i>' . get_string('letter_joinnote', $pn) . '</i></td></tr>'
            . '<tr><td colspan="2" style="text-align:justify;">' . get_string('letter_appreciate', $pn)
            . '<br /><br />' . get_string('letter_yours', $pn) . '<br />'
            . get_string('letter_coordinator', $pn) . '<br />'
            . get_string('letter_collegeshort', $pn) . '</td></tr>'
            . '</table>';

        $pdf->SetFont('helvetica', '', self::FONT_SIZE);
        $pdf->writeHTML($html, true, false, true, false, '');

        // "Digitally generated" note and verify link.
        $pdf->Ln(3);
        $pdf->SetFont('helvetica', '', 9);
        $note = '<div style="text-align:center;">' . get_string('letter_digital', $pn) . '<br />'
            . get_string('letter_verifyhere', $pn) . '<br />'
            . '<a href="' . s($verifyurl) . '">' . s($verifyurl) . '</a></div>';
        $pdf->writeHTML($note, true, false, true, false, '');

        // Contact block at the bottom of the page, under a thick line.
        $y = max($pdf->GetY() + 4, 250);
        $pdf->SetY($y);
        $pdf->SetLineWidth(0.6);
        $pdf->Line(15, $y, 195, $y);
        $pdf->Ln(2);
        $pdf->SetFont('helvetica', '', 8.5);
        $contact = '<div style="text-align:center;">'
            . get_string('letter_contactus', $pn) . '<br />'
            . get_string('letter_address', $pn) . '<br />'
            . get_string('letter_email', $pn) . '<br />'
            . get_string('letter_tel', $pn) . '</div>';
        $pdf->writeHTML($contact, true, false, true, false, '');

        return $pdf;
    }

    /**
     * Duration labels as printed on the letter.
     *
     * @return array code => label
     */
    private static function get_letter_durations(): array {
        return [
            '4weeks' => get_string('letter_dur_4weeks', 'local_training_letter'),
            '6weeks' => get_string('letter_dur_6weeks', 'local_training_letter'),
            '6months' => get_string('letter_dur_6months', 'local_training_letter'),
        ];
    }

    /**
     * A ticked or empty box.
     *
     * @param bool $ticked true for a ticked box
     * @return string HTML for the box
     */
    private static function tick(bool $ticked): string {
        return $ticked ? '<b>[X]</b>' : '[&nbsp;&nbsp;]';
    }
}