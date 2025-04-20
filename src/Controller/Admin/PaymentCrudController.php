<?php

namespace App\Controller\Admin;

use App\Entity\Payment;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Twig\Environment;
use TCPDF;
use Nucleos\DompdfBundle\Wrapper\DompdfWrapper;

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

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm(),
            AssociationField::new('reservation'),
            MoneyField::new('amount')->setCurrency('EUR'),
            ChoiceField::new('status')
                ->setChoices([
                    'En attente' => 'pending',
                    'Payé' => 'paid',
                    'Annulé' => 'cancelled',
                ]),
            DateTimeField::new('createdAt')->hideOnForm(),
            TextField::new('billingNumber')->hideOnForm(),
        ];
    }

    public function generateInvoice(Payment $payment): string
    {
        // Créer une nouvelle instance TCPDF
        $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

        // Définir les métadonnées du document
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor('Regards Singuliers');
        $pdf->SetTitle('Facture ' . $payment->getBillingNumber());
        $pdf->SetSubject('Facture');

        // Supprimer l'en-tête et le pied de page par défaut
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);

        // Définir les marges
        $pdf->SetMargins(15, 15, 15);

        // Ajouter une page
        $pdf->AddPage();

        // Générer le contenu HTML
        $html = $this->twig->render('admin/payment/invoice.html.twig', [
            'payment' => $payment,
        ]);

        // Écrire le contenu HTML
        $pdf->writeHTML($html, true, false, true, false, '');

        // Retourner le PDF généré
        return $pdf->Output('', 'S');
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
