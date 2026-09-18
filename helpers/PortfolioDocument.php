<?php

namespace app\helpers;

/**
 * tFPDF document for section portfolios: adds the page footer.
 * All layout lives in PortfolioPdf; this class only exists because FPDF
 * draws footers through an overridable Footer() hook.
 */
class PortfolioDocument extends \tFPDF
{
    /** Text before " · p. N"; set by PortfolioPdf. */
    public $footerText = '';

    public function Footer()
    {
        if ($this->PageNo() === 1) {
            return; // cover page stays clean
        }
        $this->SetY(-12);
        $this->SetFont('Jost', '', 8);
        $this->SetTextColor(150, 146, 142);
        $this->Cell(0, 6, $this->footerText . ' · p. ' . $this->PageNo(), 0, 0, 'C');
    }
}
