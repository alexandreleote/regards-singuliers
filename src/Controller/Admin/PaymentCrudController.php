<?php

namespace App\Controller\Admin;

use App\Entity\Payment;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use Nucleos\DompdfBundle\Wrapper\DompdfWrapper;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Twig\Environment;

#[Route('/admin')]
class PaymentCrudController extends AbstractCrudController
{
    public function __construct(
        private Environment $twig,
        private EntityManagerInterface $entityManager,
        private DompdfWrapper $dompdf
    ) {}

    public static function getEntityFqcn(): string
    {
        return Payment::class;
    }

    #[Route('/payment/{id}/view-invoice', name: 'admin_payment_view_invoice')]
    public function viewInvoice(Payment $payment): Response
    {
        $reservation = $payment->getReservation();
        $data = [
            'title' => 'Facture' . ' ' . $payment->getBillingNumber(),
            'invoice_number' => $payment->getBillingNumber(),
            'invoice_date' => $payment->getPaidAt(),
            'due_date' => $reservation->getAppointmentDatetime(),
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
            'payment_terms' => 'Solde à régler le jour du rendez-vous : ' . $reservation->getAppointmentDatetime()->format('d/m/Y'),
            'iban' => 'FR76 XXXX XXXX XXXX XXXX XXXX XXX',
            'bic' => 'BICXXXXXXX'
        ];

        $html = $this->twig->render('pdf/invoices.html.twig', $data);

        $pdf = $this->dompdf->getPdf($html);

        return new Response(
            $pdf,
            Response::HTTP_OK,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="facture-' . $payment->getBillingNumber() . '.pdf"'
            ]
        );
    }

    #[Route('/payment/{id}/download-invoice', name: 'admin_payment_download_invoice')]
    public function downloadInvoice(Payment $payment): Response
    {
        $reservation = $payment->getReservation();
        $data = [
            'title' => 'Facture' . ' ' . $payment->getBillingNumber(),
            'invoice_number' => $payment->getBillingNumber(),
            'invoice_date' => $payment->getPaidAt(),
            'due_date' => $reservation->getAppointmentDatetime(),
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
            'payment_terms' => 'Solde à régler le jour du rendez-vous : ' . $reservation->getAppointmentDatetime()->format('d/m/Y'),
            'iban' => 'FR76 XXXX XXXX XXXX XXXX XXXX XXX',
            'bic' => 'BICXXXXXXX'
        ];

        $html = $this->twig->render('pdf/invoices.html.twig', $data);

        $pdf = $this->dompdf->getPdf($html);

        return new Response(
            $pdf,
            Response::HTTP_OK,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="facture-' . $payment->getBillingNumber() . '.pdf"'
            ]
        );
    }
}
