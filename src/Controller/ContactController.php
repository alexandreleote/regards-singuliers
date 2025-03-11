<?php

namespace App\Controller;

use App\Entity\Contact;
use App\Entity\User;
use App\Form\ContactType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ContactController extends AbstractController
{
    #[Route('/contact', name: 'contact')]
    public function index(Request $request, EntityManagerInterface $entityManager): Response
    {
        $contact = new Contact();
        
        // Pré-remplir les données si l'utilisateur est connecté
        if ($this->getUser() instanceof User) {
            /** @var User $user */
            $user = $this->getUser();
            $contact->setNom($user->getName() ?? '');
            $contact->setPrenom($user->getFirstName() ?? '');
            $contact->setEmail($user->getEmail() ?? '');
        }
        
        $form = $this->createForm(ContactType::class, $contact);
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            // Persister le contact dans la base de données
            $entityManager->persist($contact);
            $entityManager->flush();
            
            // Ajouter un message de confirmation
            $this->addFlash('success', 'Votre message a bien été envoyé. Nous vous répondrons dans les plus brefs délais.');
            
            // Rediriger vers la page de confirmation
            return $this->redirectToRoute('contact_confirmation');
        }
        
        return $this->render('contact/index.html.twig', [
            'form' => $form->createView(),
            'page_title' => 'Contact - Regards Singuliers',
        ]);
    }
    
    #[Route('/contact/confirmation', name: 'contact_confirmation')]
    public function confirmation(): Response
    {
        return $this->render('contact/confirmation.html.twig', [
            'page_title' => 'Message envoyé - Regards Singuliers',
        ]);
    }
}