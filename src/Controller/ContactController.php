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
use Symfony\Component\Security\Core\Exception\InvalidCsrfTokenException;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Csrf\CsrfToken;

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
        EntityManagerInterface $entityManager,
        CsrfTokenManagerInterface $csrfTokenManager
    ): JsonResponse {
        try {
            $content = $request->getContent();
            $logger->info('Contenu brut reçu:', ['content' => $content]);
            
            $data = json_decode($content, true);
            $logger->info('Données décodées:', ['data' => $data]);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \InvalidArgumentException('Format JSON invalide: ' . json_last_error_msg());
            }

            // Validation CSRF
            $token = $request->headers->get('X-CSRF-TOKEN');
            if (!$token) {
                throw new InvalidCsrfTokenException('Token CSRF manquant');
            }

            if (!$csrfTokenManager->isTokenValid(new CsrfToken('contact_form', $token))) {
                throw new InvalidCsrfTokenException('Token CSRF invalide');
            }

            // Validation de base pour tous les types
            $baseConstraints = [
                'type' => [new Assert\Choice(['choices' => [Contact::TYPE_PARTICULIER, Contact::TYPE_PROFESSIONNEL]])],
                'civilite' => [new Assert\Choice(['choices' => [Contact::CIVILITE_MONSIEUR, Contact::CIVILITE_MADAME]])],
                'nom' => [
                    new Assert\NotBlank(),
                    new Assert\Length(['min' => 2, 'max' => 100]),
                    new Assert\Regex([
                        'pattern' => '/^[a-zA-ZÀ-ÿ\s\'-]+$/',
                        'message' => 'Le nom ne peut contenir que des lettres, espaces, apostrophes et tirets'
                    ])
                ],
                'prenom' => [
                    new Assert\NotBlank(),
                    new Assert\Length(['min' => 2, 'max' => 100]),
                    new Assert\Regex([
                        'pattern' => '/^[a-zA-ZÀ-ÿ\s\'-]+$/',
                        'message' => 'Le prénom ne peut contenir que des lettres, espaces, apostrophes et tirets'
                    ])
                ],
                'email' => [
                    new Assert\NotBlank(),
                    new Assert\Email(['mode' => 'strict'])
                ],
                'telephone' => [
                    new Assert\NotBlank(),
                    new Assert\Regex([
                        'pattern' => '/^[0-9]{10}$/',
                        'message' => 'Le numéro de téléphone doit contenir exactement 10 chiffres'
                    ])
                ],
                'localisation' => [new Assert\NotBlank()],
                'description' => [
                    new Assert\NotBlank(),
                    new Assert\Length(['min' => 10, 'max' => 2000])
                ]
            ];

            // Validation supplémentaire pour les professionnels
            if ($data['type'] === Contact::TYPE_PROFESSIONNEL) {
                $baseConstraints['entreprise'] = [
                    new Assert\NotBlank(),
                    new Assert\Length(['min' => 2, 'max' => 100])
                ];
            }

            $violations = $validator->validate($data, new Assert\Collection($baseConstraints));

            if (count($violations) > 0) {
                $errors = [];
                foreach ($violations as $violation) {
                    $propertyPath = $violation->getPropertyPath();
                    $errors[trim($propertyPath, '[]')] = $violation->getMessage();
                }
                return new JsonResponse(['errors' => $errors], JsonResponse::HTTP_BAD_REQUEST);
            }

            // Création et persistance du contact
            $contact = new Contact();
            $contact->setType($data['type']);
            $contact->setCivilite($data['civilite']);
            $contact->setNom(strip_tags($data['nom']));
            $contact->setPrenom(strip_tags($data['prenom']));
            $contact->setEmail(filter_var($data['email'], FILTER_SANITIZE_EMAIL));
            $contact->setTelephone(preg_replace('/[^0-9]/', '', $data['telephone']));
            $contact->setLocalisation(strip_tags($data['localisation']));
            $contact->setDescription(strip_tags($data['description']));
            $contact->setIsRead(false);
            $contact->setCreatedAt(new \DateTimeImmutable());

            if ($data['type'] === Contact::TYPE_PROFESSIONNEL) {
                $contact->setEntreprise(strip_tags($data['entreprise']));
            }

            $entityManager->persist($contact);
            $entityManager->flush();

            return new JsonResponse(['success' => true]);

        } catch (InvalidCsrfTokenException $e) {
            $logger->error('Erreur CSRF:', ['error' => $e->getMessage()]);
            return new JsonResponse([
                'success' => false,
                'error' => 'Token de sécurité invalide'
            ], JsonResponse::HTTP_FORBIDDEN);
        } catch (\Exception $e) {
            $logger->error('Erreur lors du traitement:', ['error' => $e->getMessage()]);
            return new JsonResponse([
                'success' => false,
                'error' => 'Une erreur est survenue lors du traitement de votre demande'
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
