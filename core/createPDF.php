<?php
class createPDF
{
    public static function pricesList(string $tbl, $pdf_file_path)
    {
        $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

        $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

        $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
        $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
        $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

        $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

        $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

        $lg = [
            'a_meta_charset'=>'UTF-8',
            'a_meta_dir'=>'rtl',
            'a_meta_language'=>'fa',
            'w_page'=>'page',
        ];
        $pdf->setLanguageArray($lg);

        $pdf->SetFont('XZar', '', 15, '', true);

        $pdf->AddPage();

        $pdf->Write(0, PDFTextContext::get('pricesList'), '', 0, 'R', true, 0, false, false, 0);

        $pdf->writeHTML($tbl, true, false, false, false, '');
        $pdf->Output($pdf_file_path, 'F');
    }

}