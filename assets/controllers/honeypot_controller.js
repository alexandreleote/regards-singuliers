import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    connect() {
        // Masquer les champs honeypot
        this.element.style.display = 'none';
    }

    submit(event) {
        // Si les champs honeypot sont remplis, on simule un succès
        const website = event.target.querySelector('input[name="website"]').value;
        const website2 = event.target.querySelector('input[name="website2"]').value;

        if (website || website2) {
            event.preventDefault();
            event.stopPropagation();
            return true;
        }

        return false;
    }
} 