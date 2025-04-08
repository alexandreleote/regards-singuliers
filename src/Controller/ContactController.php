<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Validator\Constraints as Assert;

#[Route('/contact')]
class ContactController extends AbstractController
{
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

            // Vérification du honeypot
            if (!empty($data['mobilePhone']) || !empty($data['workEmail'])) {
                return $this->json([
                    'success' => false,
                    'message' => 'Message non envoyé'
                ], Response::HTTP_FORBIDDEN);
            }

            // Échappement des caractères spéciaux
            $data = array_map(function($value) {
                return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
            }, $data);

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

            $violations = $this->container->get('validator')->validate($data, $constraints);

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
                // Préparation de l'email
                $email = (new Email())
                    ->from($data['email'])
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
                error_log('Erreur lors de l\'envoi du mail: ' . $e->getMessage());
                return $this->json([
                    'success' => false,
                    'message' => 'Une erreur est survenue lors de l\'envoi du message'
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