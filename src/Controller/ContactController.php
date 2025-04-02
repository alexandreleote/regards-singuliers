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
            // Vérification du honeypot
            if (!empty($request->request->get('website')) || !empty($request->request->get('website2'))) {
                return $this->json(['success' => true, 'message' => 'Message envoyé avec succès']);
            }

            // Validation des données
            $constraints = new Assert\Collection([
                'type' => [new Assert\NotBlank(), new Assert\Choice(['particulier', 'professionnel'])],
                'civilite' => [new Assert\NotBlank(), new Assert\Choice(['M.', 'Mme'])],
                'nom' => [new Assert\NotBlank(), new Assert\Length(['min' => 2, 'max' => 100])],
                'prenom' => [new Assert\NotBlank(), new Assert\Length(['min' => 2, 'max' => 100])],
                'email' => [new Assert\NotBlank(), new Assert\Email()],
                'telephone' => [new Assert\NotBlank(), new Assert\Regex(['pattern' => '/^[0-9+\s()]+$/', 'message' => 'Le numéro de téléphone n\'est pas valide'])],
                'localisation' => [new Assert\NotBlank(), new Assert\Length(['min' => 2, 'max' => 100])],
                'entreprise' => [new Assert\Optional([new Assert\Length(['min' => 2, 'max' => 100])])],
                'message' => [new Assert\NotBlank(), new Assert\Length(['min' => 10, 'max' => 1000])],
            ]);

            $violations = $this->container->get('validator')->validate($request->request->all(), $constraints);

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

            // Préparation de l'email
            $email = (new Email())
                ->from($request->request->get('email'))
                ->to('hello@regards-singuliers.com')
                ->subject('Nouveau message de contact - regards singuliers')
                ->html($this->renderView('contact/email.html.twig', [
                    'type' => $request->request->get('type'),
                    'civilite' => $request->request->get('civilite'),
                    'nom' => $request->request->get('nom'),
                    'prenom' => $request->request->get('prenom'),
                    'email' => $request->request->get('email'),
                    'telephone' => $request->request->get('telephone'),
                    'localisation' => $request->request->get('localisation'),
                    'entreprise' => $request->request->get('entreprise'),
                    'message' => $request->request->get('message')
                ]));

            try {
                $mailer->send($email);
                return $this->json(['success' => true, 'message' => 'Message envoyé avec succès']);
            } catch (\Exception $e) {
                return $this->json([
                    'success' => false,
                    'message' => 'Une erreur est survenue lors de l\'envoi du message'
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
            }
        }

        return $this->render('contact/index.html.twig', [
            'page_title' => 'Contact - regards singuliers',
            'meta_description' => 'Contactez regards singuliers pour vos projets d\'architecture d\'intérieur. Nous sommes à votre écoute pour transformer vos espaces.'
        ]);
    }
} 