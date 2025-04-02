import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['field'];

    connect() {
        // Rendre le champ inaccessible aux lecteurs d'écran et aux utilisateurs
        this.fieldTarget.setAttribute('inert', '');
        this.fieldTarget.style.display = 'none';
    }

    disconnect() {
        // Nettoyer lors de la déconnexion
        this.fieldTarget.removeAttribute('inert');
        this.fieldTarget.style.display = '';
    }
} 