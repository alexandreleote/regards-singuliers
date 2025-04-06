import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['menu', 'overlay']
    
    connect() {
        // Ajouter l'action pour fermer le menu lors du clic sur l'overlay
        this.overlayTarget.addEventListener('click', () => this.close());
        
        // Fermer le menu lors du clic sur un lien
        this.menuTarget.querySelectorAll('.nav-link-mobile').forEach(link => {
            link.addEventListener('click', () => this.close());
        });
    }

    toggle() {
        if (this.element.classList.contains('active')) {
            this.close();
        } else {
            this.open();
        }
    }

    open() {
        this.element.classList.add('active');
        this.menuTarget.classList.add('active');
        this.overlayTarget.classList.add('active');
        document.querySelector('.burger-menu-btn').classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    close() {
        this.element.classList.remove('active');
        this.menuTarget.classList.remove('active');
        this.overlayTarget.classList.remove('active');
        document.querySelector('.burger-menu-btn').classList.remove('active');
        document.body.style.overflow = '';
    }
} 