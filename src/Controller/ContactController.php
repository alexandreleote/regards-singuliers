<?php

namespace App\Controller;

use App\Entity\Contact;
use App\Repository\ContactRepository;
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
use App\Entity\BotIp;

final class ContactController extends AbstractController
{
    private $contactRepository;
    private $entityManager;
    private $logger;
    private $validator;
    private $csrfTokenManager;

    public function __construct(
        ContactRepository $contactRepository,
        EntityManagerInterface $entityManager,
        LoggerInterface $logger,
        ValidatorInterface $validator,
        CsrfTokenManagerInterface $csrfTokenManager
    ) {
        $this->contactRepository = $contactRepository;
        $this->entityManager = $entityManager;
        $this->logger = $logger;
        $this->validator = $validator;
        $this->csrfTokenManager = $csrfTokenManager;
    }

    #[Route('/contact', name: 'contact')]
    public function index(): Response
    {
        return $this->render('contact/index.html.twig', [
            'page_title' => 'Contact - regards singuliers',
            'meta_description' => 'Contactez le studio regards singuliers pour toute demande d\'information, de devis ou pour discuter de vos projets d\'aménagement de votre intérieur. Nous sommes à votre écoute !',
            'google_maps_api_key' => $this->getParameter('app.google_maps_api_key'),
        ]);
    }

    #[Route('/contact/submit', name: 'contact_submit', methods: ['POST'])]
    public function submit(Request $request): Response
    {
        try {
            $content = $request->getContent();
            $this->logger->info('Contenu brut reçu:', ['content' => $content]);
            
            $data = json_decode($content, true);
            $this->logger->info('Données décodées:', ['data' => $data]);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \InvalidArgumentException('Format JSON invalide: ' . json_last_error_msg());
            }

            // Validation CSRF
            $token = $request->headers->get('X-CSRF-TOKEN');
            if (!$token) {
                throw new InvalidCsrfTokenException('Token CSRF manquant');
            }

            if (!$this->csrfTokenManager->isTokenValid(new CsrfToken('contact_form', $token))) {
                throw new InvalidCsrfTokenException('Token CSRF invalide');
            }

            // Vérification des champs honeypot
            if (!empty($data['mobilePhone']) || !empty($data['workEmail'])) {
                // Enregistrer l'IP du bot (hashée)
                $botIp = new BotIp();
                $botIp->setIp(hash('sha256', $request->getClientIp()));
                $botIp->setUserAgent($request->headers->get('User-Agent'));
                $botIp->setFormType('contact_form');

                $this->entityManager->persist($botIp);
                $this->entityManager->flush();

                $this->logger->info('Tentative de bot détectée', [
                    'ip' => $request->getClientIp(),
                    'user_agent' => $request->headers->get('User-Agent'),
                    'mobile_phone' => $data['mobilePhone'],
                    'work_email' => $data['workEmail']
                ]);

                return new JsonResponse([
                    'success' => false,
                    'error' => 'Votre demande a été acceptée - ou pas.'
                ], JsonResponse::HTTP_FORBIDDEN);
            }

            // Validation de base pour tous les types
            $baseConstraints = [
                'type' => [
                    new Assert\NotBlank(),
                    new Assert\Choice(['choices' => [Contact::TYPE_PARTICULIER, Contact::TYPE_PROFESSIONNEL]])
                ],
                'civilite' => [
                    new Assert\NotBlank(),
                    new Assert\Choice(['choices' => [Contact::CIVILITE_MONSIEUR, Contact::CIVILITE_MADAME]])
                ],
                'nom' => [
                    new Assert\NotBlank(),
                    new Assert\Length(['min' => 2, 'max' => 100])
                ],
                'prenom' => [
                    new Assert\NotBlank(),
                    new Assert\Length(['min' => 2, 'max' => 100])
                ],
                'email' => [
                    new Assert\NotBlank(),
                    new Assert\Email()
                ],
                'telephone' => [
                    new Assert\NotBlank(),
                    new Assert\Regex([
                        'pattern' => '/^[0-9\s\+\-\.]{10,15}$/',
                        'message' => 'Le numéro de téléphone n\'est pas valide'
                    ])
                ],
                'localisation' => [
                    new Assert\NotBlank(),
                    new Assert\Length(['max' => 255])
                ],
                'description' => [
                    new Assert\NotBlank(),
                    new Assert\Length(['min' => 10])
                ]
            ];

            if ($data['type'] === Contact::TYPE_PROFESSIONNEL) {
                $baseConstraints['entreprise'] = [
                    new Assert\NotBlank(),
                    new Assert\Length(['min' => 2, 'max' => 100])
                ];
            }

            // Supprimer les champs honeypot des données avant la validation
            unset($data['mobilePhone'], $data['workEmail']);

            $violations = $this->validator->validate($data, new Assert\Collection($baseConstraints));

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

            $this->entityManager->persist($contact);
            $this->entityManager->flush();

            return new JsonResponse(['success' => true]);

        } catch (InvalidCsrfTokenException $e) {
            $this->logger->error('Erreur CSRF:', ['error' => $e->getMessage()]);
            return new JsonResponse([
                'success' => false,
                'error' => 'Token de sécurité invalide'
            ], JsonResponse::HTTP_FORBIDDEN);
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors du traitement:', ['error' => $e->getMessage()]);
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
            'page_title' => 'Demande envoyée - regards singuliers',
            'meta_description' => 'Votre demande a été envoyée avec succès à regards singuliers. Nous reviendrons vers vous dans les plus brefs délais pour répondre à votre requête.',
        ]);
    }
}
