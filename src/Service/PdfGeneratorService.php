<?php

namespace App\Service;

use Nucleos\DompdfBundle\Wrapper\DompdfWrapper;
use Twig\Environment;

class PdfGeneratorService
{
    private $dompdf;
    private $twig;

    public function __construct(DompdfWrapper $dompdf, Environment $twig)
    {
        $this->dompdf = $dompdf;
        $this->twig = $twig;
    }

    public function generateInvoicePdf(array $data): string
    {
        $html = $this->twig->render('pdf/invoices.html.twig', $data);

        $output = $this->dompdf->getPdf($html);

        return $output;
    }
}