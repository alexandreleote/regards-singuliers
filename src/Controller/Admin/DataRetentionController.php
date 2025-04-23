<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Repository\ReservationRepository;
use App\Repository\PaymentRepository;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DataRetentionController extends AbstractDashboardController
{
    private const SERVICE_DATA_RETENTION_YEARS = 5;
    private const PROSPECT_DATA_RETENTION_YEARS = 3;
    private const ACCOUNTING_DATA_RETENTION_YEARS = 10;

    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserRepository $userRepository,
        private ReservationRepository $reservationRepository,
        private PaymentRepository $paymentRepository
    ) {}

    #[Route('/admin/data-retention', name: 'admin_data_retention')]
    public function index(): Response
    {
        $today = new \DateTimeImmutable();
        
        return $this->render('admin/data_retention/index.html.twig', [
            'page_title' => 'Gestion de la rétention des données',
            'service_data_date' => $this->calculateLegalDate($today, self::SERVICE_DATA_RETENTION_YEARS),
            'prospect_data_date' => $this->calculateLegalDate($today, self::PROSPECT_DATA_RETENTION_YEARS),
            'accounting_data_date' => $this->calculateLegalDate($today, self::ACCOUNTING_DATA_RETENTION_YEARS),
        ]);
    }

    private function calculateLegalDate(\DateTimeImmutable $today, int $years): \DateTimeImmutable
    {
        return $today->modify(sprintf('-%d years', $years));
    }

    #[Route('/admin/data-retention/delete-service-data', name: 'admin_delete_service_data', methods: ['POST'])]
    public function deleteServiceData(): Response
    {
        $legalDate = $this->calculateLegalDate(new \DateTimeImmutable(), self::SERVICE_DATA_RETENTION_YEARS);
        
        $reservations = $this->reservationRepository->findOlderThan($legalDate);
        $count = 0;
        
        foreach ($reservations as $reservation) {
            $this->entityManager->remove($reservation);
            $count++;
        }
        
        $this->entityManager->flush();
        
        $this->addFlash('success', sprintf(
            '%d réservations antérieures au %s ont été supprimées.',
            $count,
            $legalDate->format('d/m/Y')
        ));
        return $this->redirectToRoute('admin_data_retention');
    }

    #[Route('/admin/data-retention/delete-prospect-data', name: 'admin_delete_prospect_data', methods: ['POST'])]
    public function deleteProspectData(): Response
    {
        $legalDate = $this->calculateLegalDate(new \DateTimeImmutable(), self::PROSPECT_DATA_RETENTION_YEARS);
        
        $prospects = $this->userRepository->findProspectsOlderThan($legalDate);
        $count = 0;
        
        foreach ($prospects as $prospect) {
            $this->entityManager->remove($prospect);
            $count++;
        }
        
        $this->entityManager->flush();
        
        $this->addFlash('success', sprintf(
            '%d prospects antérieurs au %s ont été supprimés.',
            $count,
            $legalDate->format('d/m/Y')
        ));
        return $this->redirectToRoute('admin_data_retention');
    }

    #[Route('/admin/data-retention/delete-accounting-data', name: 'admin_delete_accounting_data', methods: ['POST'])]
    public function deleteAccountingData(): Response
    {
        $legalDate = $this->calculateLegalDate(new \DateTimeImmutable(), self::ACCOUNTING_DATA_RETENTION_YEARS);
        
        // Supprimer les paiements de plus de 10 ans
        $payments = $this->paymentRepository->findOlderThan($legalDate);
        $paymentCount = 0;
        
        foreach ($payments as $payment) {
            $this->entityManager->remove($payment);
            $paymentCount++;
        }

        // Supprimer les réservations de plus de 10 ans (qui incluent les données comptables)
        $reservations = $this->reservationRepository->findOlderThan($legalDate);
        $reservationCount = 0;
        
        foreach ($reservations as $reservation) {
            $this->entityManager->remove($reservation);
            $reservationCount++;
        }
        
        $this->entityManager->flush();
        
        $this->addFlash('success', sprintf(
            '%d paiements et %d réservations antérieurs au %s ont été supprimés.',
            $paymentCount,
            $reservationCount,
            $legalDate->format('d/m/Y')
        ));
        return $this->redirectToRoute('admin_data_retention');
    }
} 