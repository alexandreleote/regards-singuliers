<?php

namespace App\Controller;

use App\Entity\Contact;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class ContactController extends AbstractController
{
    #[Route('/contact', name: 'contact')]
    public function index(): Response
    {
        return $this->render('contact/index.html.twig', [
            'page_title' => 'regards singuliers - Contact',
            'google_maps_api_key' => $this->getParameter('app.google_maps_api_key'),
        ]);
    }

    #[Route('/contact/submit', name: 'contact_submit', methods: ['POST'])]
    public function submit(
        Request $request, 
        ValidatorInterface $validator, 
        LoggerInterface $logger,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        $content = $request->getContent();
        $logger->info('Contenu brut reçu:', ['content' => $content]);
        
        $data = json_decode($content, true);
        $logger->info('Données décodées:', ['data' => $data]);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $logger->error('Erreur de décodage JSON:', ['error' => json_last_error_msg()]);
            return new JsonResponse([
                'success' => false,
                'error' => 'Format JSON invalide: ' . json_last_error_msg()
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        // Validation de base pour tous les types
        $baseConstraints = [
            'type' => [new Assert\Choice(['choices' => ['particular', 'professional']])],
            'name' => [new Assert\NotBlank(), new Assert\Length(['min' => 2, 'max' => 100])],
            'firstname' => [new Assert\NotBlank(), new Assert\Length(['min' => 2, 'max' => 100])],
            'email' => [new Assert\NotBlank(), new Assert\Email()],
            'phone' => [new Assert\Regex([
                'pattern' => '/^(0[1-7]) [0-9]{2} [0-9]{2} [0-9]{2} [0-9]{2}$/',
                'message' => 'Le numéro de téléphone doit commencer par 01, 02, 03, 04, 05, 06 ou 07 et être au format XX XX XX XX XX'
            ])],
            'location' => [new Assert\NotBlank()],
            'project' => [
                new Assert\NotBlank(['message' => 'La description du projet est obligatoire']),
                new Assert\Length([
                    'min' => 10,
                    'minMessage' => 'La description du projet doit contenir au moins {{ limit }} caractères',
                    'max' => 2000,
                    'maxMessage' => 'La description du projet ne peut pas dépasser {{ limit }} caractères'
                ])
            ]
        ];

        // Ajouter la validation du champ company uniquement pour le type professional
        if (isset($data['type']) && $data['type'] === 'professional') {
            $baseConstraints['company'] = [
                new Assert\NotBlank(['message' => 'Le champ entreprise est obligatoire pour les professionnels']),
                new Assert\Length(['min' => 2, 'max' => 100])
            ];
        } else {
            $baseConstraints['company'] = [new Assert\Optional()];
        }

        $constraints = new Assert\Collection($baseConstraints);
        $violations = $validator->validate($data, $constraints);

        if ($violations->count() > 0) {
            $errors = [];
            foreach ($violations as $violation) {
                $errors[$violation->getPropertyPath()] = $violation->getMessage();
            }
            $logger->error('Erreurs de validation:', ['errors' => $errors]);
            return new JsonResponse(['success' => false, 'errors' => $errors], JsonResponse::HTTP_BAD_REQUEST);
        }

        try {
            // Créer une nouvelle instance de Contact
            $contact = new Contact();
            $contact->setType($data['type']);
            $contact->setName($data['name']);
            $contact->setFirstname($data['firstname']);
            $contact->setEmail($data['email']);
            $contact->setPhone($data['phone']);
            $contact->setCompany($data['company'] ?? null);
            $contact->setLocation($data['location']);
            $contact->setProject($data['project']);
            $contact->setCreatedAt(new \DateTimeImmutable());
            $contact->setStatus('new');

            // Persister l'entité
            $entityManager->persist($contact);
            $entityManager->flush();

            $logger->info('Contact enregistré avec succès', [
                'id' => $contact->getId(),
                'type' => $contact->getType()
            ]);

            return new JsonResponse(['success' => true]);
        } catch (\Exception $e) {
            $logger->error('Erreur lors de l\'enregistrement du contact:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return new JsonResponse([
                'success' => false,
                'error' => 'Une erreur est survenue lors de l\'enregistrement du contact.'
            ], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
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
