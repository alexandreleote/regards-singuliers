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
    #[Route('/facture/{billingNumber}', name: 'pdf_invoice')]
    public function index(
        PdfGeneratorService $pdfGeneratorService,
        PaymentRepository $paymentRepository,
        string $billingNumber,
        Request $request
    ): Response {
        $payment = $paymentRepository->findOneBy(['billingNumber' => $billingNumber]);
        if (!$payment) {
            throw $this->createNotFoundException('Paiement non trouvé');
        }

        $reservation = $payment->getReservation();
        $user = $reservation->getUser();
        $service = $reservation->getService();

        $data = [
            'title' => 'Facture',
            'invoice_number' => $payment->getBillingNumber(),
            'invoice_date' => $payment->getPaidAt(),
            'client_name' => $user->getFullName(),
            'client_address' => $user->getAddress(),
            'service_name' => $service->getTitle(),
            'appointment_date' => $reservation->getAppointmentDatetime(),
            'items' => [
                [
                    'description' => $service->getTitle(),
                    'quantity' => 1,
                    'unit_price' => $service->getPrice(),
                ]
            ],
            'total_ht' => $service->getPrice(),
            'deposit_amount' => $payment->getDepositAmount(),
            'payment_terms' => 'Solde à régler le jour du rendez-vous : ' . $reservation->getAppointmentDatetime()->format('d/m/Y'),
            'iban' => 'FR76 XXXX XXXX XXXX XXXX XXXX XXX',
            'bic' => 'BICXXXXXXX',
        ];

        try {
            $pdfContent = $pdfGeneratorService->generateInvoicePdf($data);
        } catch (\Exception $e) {
            throw new \RuntimeException('Erreur lors de la génération du PDF : ' . $e->getMessage());
        }

        $response = new Response($pdfContent);
        $response->headers->set('Content-Type', 'application/pdf');
        $response->headers->set('Content-Disposition', 'inline; filename="facture-' . $payment->getBillingNumber() . '.pdf"');
        
        return $response;
    }

    #[Route('/facture/{billingNumber}/download', name: 'pdf_invoice_download')]
    public function download(
        PdfGeneratorService $pdfGeneratorService,
        PaymentRepository $paymentRepository,
        string $billingNumber
    ): Response {
        $payment = $paymentRepository->findOneBy(['billingNumber' => $billingNumber]);
        if (!$payment) {
            throw $this->createNotFoundException('Paiement non trouvé');
        }
        $reservation = $payment->getReservation();
        $user = $reservation->getUser();
        $service = $reservation->getService();
        $data = [
            'title' => 'Facture',
            'invoice_number' => $payment->getBillingNumber(),
            'invoice_date' => $payment->getPaidAt(),
            'client_name' => $user->getFullName(),
            'client_address' => $user->getAddress(),
            'service_name' => $service->getTitle(),
            'appointment_date' => $reservation->getAppointmentDatetime(),
            'items' => [
                [
                    'description' => $service->getTitle(),
                    'quantity' => 1,
                    'unit_price' => $service->getPrice(),
                ]
            ],
            'total_ht' => $service->getPrice(),
            'deposit_amount' => $payment->getDepositAmount(),
            'payment_terms' => 'Solde à régler le jour du rendez-vous : ' . $reservation->getAppointmentDatetime()->format('d/m/Y'),
            'iban' => 'FR76 XXXX XXXX XXXX XXXX XXXX XXX',
            'bic' => 'BICXXXXXXX',
        ];
        $pdfContent = $pdfGeneratorService->generateInvoicePdf($data);

        $response = new Response($pdfContent);
        $response->headers->set('Content-Type', 'application/pdf');
        $response->headers->set('Content-Disposition', 'attachment; filename="facture-' . $payment->getBillingNumber() . '.pdf"');
        return $response;
    }
}
