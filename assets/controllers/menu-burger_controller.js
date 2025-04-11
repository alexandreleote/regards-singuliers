import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['mainMenu', 'authMenu', 'overlay'];

    connect() {
        this.isMenuOpen = false;
        this.isAuthMenuOpen = false;
        this.overlay = this.overlayTarget;
    }

    toggle(event) {
        event.preventDefault();
        this.isMenuOpen = !this.isMenuOpen;
        this.mainMenuTarget.classList.toggle('active');
        
        // Gérer l'overlay et le scroll du body
        this.updateOverlayAndBodyScroll();
        
        // Toggle le bouton du burger menu
        event.currentTarget.classList.toggle('active');
    }

    toggleAuth(event) {
        event.preventDefault();
        
        // Fermer le menu principal s'il est ouvert
        if (this.isMenuOpen) {
            this.isMenuOpen = false;
            this.mainMenuTarget.classList.remove('active');
            document.querySelector('.burger-menu-btn').classList.remove('active');
        }
        
        // Toggle le menu d'authentification
        this.isAuthMenuOpen = !this.isAuthMenuOpen;
        this.authMenuTarget.classList.toggle('active');
        
        // Gérer l'overlay et le scroll du body
        this.updateOverlayAndBodyScroll();
        
        // Toggle le bouton du auth menu
        event.currentTarget.classList.toggle('active');
        
        console.log('Auth menu toggled:', this.isAuthMenuOpen);
    }

    // Méthode pour gérer l'état de l'overlay et du scroll body
    updateOverlayAndBodyScroll() {
        // Si l'un des menus est ouvert, afficher l'overlay et bloquer le scroll
        if (this.isMenuOpen || this.isAuthMenuOpen) {
            this.overlay.classList.add('active');
            document.body.style.overflow = 'hidden';
        } else {
            // Si les deux sont fermés, masquer l'overlay et réactiver le scroll
            this.overlay.classList.remove('active');
            document.body.style.overflow = '';
        }
    }

    closeMenu(event) {
        // Fermer les deux menus si l'overlay est cliqué
        if (this.isMenuOpen) {
            this.isMenuOpen = false;
            this.mainMenuTarget.classList.remove('active');
            document.querySelector('.burger-menu-btn').classList.remove('active');
        }
        
        if (this.isAuthMenuOpen) {
            this.isAuthMenuOpen = false;
            this.authMenuTarget.classList.remove('active');
            document.querySelector('.auth-burger-menu-btn').classList.remove('active');
        }
        
        // Mettre à jour l'overlay et le scroll body
        this.updateOverlayAndBodyScroll();
    }
} 