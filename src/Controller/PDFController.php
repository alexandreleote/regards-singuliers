<?php

namespace App\Controller;

use App\Service\PdfGeneratorService;
use App\Repository\PaymentRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpFoundation\Request;

class PDFController extends AbstractController
{
    #[Route('/facture/{billing_number}', name: 'pdf_invoice')]
    public function index(PdfGeneratorService $pdfGeneratorService, PaymentRepository $paymentRepository, string $billing_number, Request $request): Response
    {
        $payment = $paymentRepository->findOneBy(['billingNumber' => $billing_number]);
        
        if (!$payment) {
            throw new NotFoundHttpException('Facture non trouvée');
        }

        $reservation = $payment->getReservation();

        $data = [
            'title' => 'Facture' . ' ' . $payment->getBillingNumber(),
            'invoice_number' => $payment->getBillingNumber(),
            'invoice_date' => $payment->getPaidAt(),
            'due_date' => $payment->getBillingDate(),
            'client_name' => $payment->getFirstName() . ' ' . $payment->getName(),
            'client_address' => $payment->getBillingAddress(),
            'appointment_date' => $reservation->getAppointmentDatetime(),
            'service_name' => $reservation->getService()->getTitle(),
            'items' => [
                [
                    'description' => $reservation->getService()->getTitle(),
                    'quantity' => 1,
                    'unit_price' => $payment->getTotalAmount()
                ]
            ],
            'total_ht' => $payment->getTotalAmount(),
            'deposit_amount' => $payment->getDepositAmount(),
            'payment_terms' => 'Solde à régler le : ' . $reservation->getAppointmentDatetime()->format('d/m/Y'),
            'iban' => 'FR76 XXXX XXXX XXXX XXXX XXXX XXX',
            'bic' => 'BICXXXXXXX'
        ];

        $pdfContent = $pdfGeneratorService->generateInvoicePdf($data);

        $response = new Response($pdfContent);
        $response->headers->set('Content-Type', 'application/pdf');
        
        // Créer deux réponses : une pour l'affichage, une pour le téléchargement
        $filename = 'facture-' . $payment->getBillingNumber() . '.pdf';
        
        if ($request->query->get('download')) {
            $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '"');
        } else {
            $response->headers->set('Content-Disposition', 'inline; filename="' . $filename . '"');
        }

        return $response;
    }
}
