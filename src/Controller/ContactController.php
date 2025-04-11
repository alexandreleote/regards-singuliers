<?php

namespace App\Controller;

use App\Entity\BotIp;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use App\Service\SecurityService;

#[Route('/contact')]
class ContactController extends AbstractController
{
    private $validator;
    private $entityManager;
    private $securityService;

    public function __construct(
        ValidatorInterface $validator,
        EntityManagerInterface $entityManager,
        SecurityService $securityService
    )
    {
        $this->validator = $validator;
        $this->entityManager = $entityManager;
        $this->securityService = $securityService;
    }
    #[Route('/', name: 'contact', methods: ['GET', 'POST'])]
    public function index(Request $request, MailerInterface $mailer): Response
    {
        if ($request->isMethod('POST')) {
            // Récupérer les données JSON
            $data = json_decode($request->getContent(), true);
            
            if (!$data) {
                return $this->json([
                    'success' => false,
                    'message' => 'Données invalides'
                ], Response::HTTP_BAD_REQUEST);
            }

            // Vérification des champs honeypot
            $honeypotFields = ['contact_email', 'mobile_phone'];
            foreach ($honeypotFields as $field) {
                if (!empty($data[$field])) {
                    // Enregistrer l'IP suspecte
                    $botIp = new BotIp();
                    $botIp->setIp($request->getClientIp());
                    $botIp->setUserAgent($request->headers->get('User-Agent'));
                    $botIp->setFormType('contact');
                    
                    $this->entityManager->persist($botIp);
                    $this->entityManager->flush();

                    return $this->json([
                        'success' => false,
                        'message' => 'Message non envoyé'
                    ], Response::HTTP_FORBIDDEN);
                }
            }

            // Nettoyage et validation des données - sans échapper les caractères spéciaux pour l'email
            $data = array_map(function($value) {
                if (is_string($value)) {
                    // Supprimer les caractères non imprimables et les balises HTML
                    return preg_replace('/[\x00-\x1F\x7F]/u', '', strip_tags($value));
                    // Ne pas échapper les caractères spéciaux pour permettre leur affichage correct dans l'email
                }
                return $value;
            }, $data);

            // Supprimer les champs honeypot et autres champs techniques
            $excludedFields = ['contact_email', 'mobile_phone', '_timestamp', '_token'];
            foreach ($excludedFields as $field) {
                unset($data[$field]);
            }

            // Validation des données
            $constraints = new Assert\Collection([
                'type' => [new Assert\NotBlank(), new Assert\Choice(['particulier', 'professionnel'])],
                'civilite' => [new Assert\NotBlank(), new Assert\Choice(['monsieur', 'madame'])],
                'nom' => [new Assert\NotBlank(), new Assert\Length(['min' => 2, 'max' => 100])],
                'prenom' => [new Assert\NotBlank(), new Assert\Length(['min' => 2, 'max' => 100])],
                'email' => [new Assert\NotBlank(), new Assert\Email()],
                'telephone' => [new Assert\NotBlank(), new Assert\Regex(['pattern' => '/^[0-9]{10}$/', 'message' => 'Le numéro de téléphone n\'est pas valide'])],
                'localisation' => [new Assert\NotBlank(), new Assert\Length(['min' => 2, 'max' => 100])],
                'entreprise' => [new Assert\Optional([new Assert\Length(['min' => 2, 'max' => 100])])],
                'description' => [new Assert\NotBlank(), new Assert\Length(['min' => 10, 'max' => 1000])],
            ]);

            $violations = $this->validator->validate($data, $constraints);

            if (count($violations) > 0) {
                return $this->json([
                    'success' => false,
                    'errors' => array_map(function($violation) {
                        return [
                            'field' => $violation->getPropertyPath(),
                            'message' => $violation->getMessage()
                        ];
                    }, iterator_to_array($violations))
                ], Response::HTTP_BAD_REQUEST);
            }

            try {
                error_log('Début du traitement du formulaire de contact');
                error_log('Données reçues : ' . print_r($data, true));
                // Préparation de l'email
                $email = (new Email())
                    ->from('hello@regards-singuliers.com')
                    ->replyTo($data['email'])
                    ->to('hello@regards-singuliers.com')
                    ->subject('Nouveau message de contact - regards singuliers')
                    ->html($this->renderView('contact/contact.html.twig', [
                        'type' => $data['type'],
                        'civilite' => $data['civilite'],
                        'nom' => $data['nom'],
                        'prenom' => $data['prenom'],
                        'email' => $data['email'],
                        'telephone' => $data['telephone'],
                        'localisation' => $data['localisation'],
                        'entreprise' => $data['entreprise'] ?? null,
                        'message' => $data['description']
                    ]));

                $mailer->send($email);
                
                return $this->json([
                    'success' => true,
                    'message' => 'Message envoyé avec succès'
                ]);
            } catch (\Exception $e) {
                // Log détaillé de l'erreur
                $errorMessage = sprintf(
                    "Erreur lors de l'envoi du mail:\nMessage: %s\nFile: %s\nLine: %d\nTrace:\n%s",
                    $e->getMessage(),
                    $e->getFile(),
                    $e->getLine(),
                    $e->getTraceAsString()
                );
                error_log($errorMessage);

                return $this->json([
                    'success' => false,
                    'message' => 'Une erreur est survenue lors de l\'envoi du message: ' . $e->getMessage()
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
            }
        }

        return $this->render('contact/index.html.twig', [
            'page_title' => 'Contact',
            'meta_description' => 'Contactez-nous pour toute demande de service ou pour obtenir plus d\'informations sur nos prestations.',
            'google_maps_api_key' => $this->getParameter('app.google_maps_api_key')
        ]);
    }
}