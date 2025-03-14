<?php

namespace App\Controller;

use App\Entity\Contact;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Psr\Log\LoggerInterface;

final class ContactController extends AbstractController
{
    #[Route('/contact', name: 'contact')]
    public function index(): Response
    {
        return $this->render('contact/index.html.twig', [
            'page_title' => 'regards singuliers - Contact',
        ]);
    }

    #[Route('/contact/submit', name: 'contact_submit', methods: ['POST'])]
    public function submit(Request $request, EntityManagerInterface $entityManager, LoggerInterface $logger): Response
    {
        try {
            $content = $request->getContent();
            $logger->info('Contenu brut reçu:', ['content' => $content]);
            
            $data = json_decode($content, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                $logger->error('Erreur de décodage JSON:', ['error' => json_last_error_msg()]);
                return $this->json([
                    'success' => false,
                    'error' => 'Format JSON invalide: ' . json_last_error_msg()
                ], Response::HTTP_BAD_REQUEST);
            }

            if (!$data) {
                $logger->error('Données JSON vides reçues');
                return $this->json([
                    'success' => false,
                    'error' => 'Données vides'
                ], Response::HTTP_BAD_REQUEST);
            }

            $logger->info('Données reçues:', $data);

            // Validation des champs requis
            $requiredFields = ['lastName', 'firstName', 'email', 'phone', 'location', 'description'];
            foreach ($requiredFields as $field) {
                if (empty($data[$field])) {
                    $logger->error('Champ requis manquant:', ['field' => $field]);
                    return $this->json([
                        'success' => false,
                        'error' => "Le champ '$field' est requis"
                    ], Response::HTTP_BAD_REQUEST);
                }
            }

            $contact = new Contact();
            $contact->setType($data['type'] ?? 'particular');
            $contact->setLastName($data['lastName']);
            $contact->setFirstName($data['firstName']);
            $contact->setEmail($data['email']);
            $contact->setPhone($data['phone']);
            $contact->setCompany($data['company'] ?? null);
            $contact->setLocation($data['location']);
            $contact->setDescription($data['description']);

            $entityManager->persist($contact);
            $entityManager->flush();

            $logger->info('Contact enregistré avec succès', ['id' => $contact->getId()]);
            return $this->json(['success' => true]);
        } catch (\Exception $e) {
            $logger->error('Erreur lors de la soumission du formulaire:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return $this->json([
                'success' => false,
                'error' => 'Une erreur est survenue lors de l\'enregistrement: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/contact/confirmation', name: 'contact_confirmation')]
    public function confirmation(): Response
    {
        return $this->render('contact/confirmation.html.twig', [
            'page_title' => 'regards singuliers - Confirmation',
        ]);
    }
}
