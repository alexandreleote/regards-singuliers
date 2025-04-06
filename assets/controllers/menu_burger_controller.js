import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['menu', 'overlay']
    
    connect() {
        // Initialiser le menu mobile
        this.menuTarget = document.querySelector('.nav-mobile');
        
        // Créer l'overlay
        this.overlayTarget = document.createElement('div');
        this.overlayTarget.className = 'menu-overlay';
        document.body.appendChild(this.overlayTarget);
        
        // Ajouter l'action pour fermer le menu lors du clic sur l'overlay
        this.overlayTarget.addEventListener('click', () => this.close());
        
        // Fermer le menu lors du clic sur un lien
        this.menuTarget.querySelectorAll('.nav-link-mobile').forEach(link => {
            link.addEventListener('click', () => this.close());
        });

        // S'assurer que le menu est fermé initialement
        this.close();
    }

    toggle() {
        const isOpen = this.element.classList.contains('active');
        
        if (!isOpen) {
            // Ouvrir le menu
            this.element.classList.add('active');
            this.menuTarget.classList.add('open');
            this.overlayTarget.classList.add('active');
            document.body.style.overflow = 'hidden'; // Empêcher le défilement
        } else {
            // Fermer le menu
            this.close();
        }
    }

    close() {
        this.element.classList.remove('active');
        this.menuTarget.classList.remove('open');
        this.overlayTarget.classList.remove('active');
        document.body.style.overflow = ''; // Réactiver le défilement
    }

    disconnect() {
        // Nettoyer les événements et l'overlay
        this.menuTarget.querySelectorAll('.nav-link-mobile').forEach(link => {
            link.removeEventListener('click', () => this.close());
        });
        this.overlayTarget.removeEventListener('click', () => this.close());
        
        // Fermer le menu et supprimer l'overlay
        this.close();
        if (this.overlayTarget && this.overlayTarget.parentNode) {
            this.overlayTarget.remove();
        }
    }
} 