import { Controller } from '@hotwired/stimulus';

/**
 * Contrôleur pour gérer les accordéons
 * @class AccordionController
 * @extends Controller
 */
export default class extends Controller {
    static targets = ['title', 'content'];

    connect() {
        // Initialiser l'état de l'accordéon
        this.isOpen = false;
    }

    /**
     * Basculer l'état de l'accordéon
     */
    toggle() {
        this.isOpen = !this.isOpen;
        
        // Mettre à jour la classe active sur le titre
        this.titleTarget.classList.toggle('active', this.isOpen);
        
        // Afficher/masquer le contenu
        this.contentTarget.classList.toggle('hidden', !this.isOpen);
        
        // Mettre à jour l'attribut aria-hidden pour l'accessibilité
        this.contentTarget.setAttribute('aria-hidden', !this.isOpen);
    }
}
