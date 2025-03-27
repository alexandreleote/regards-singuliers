// assets/controllers/delete-account_controller.js
import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['form', 'submitButton'];
    static values = {
        confirmationMessage: String
    };

    connect() {
        this.handleFormSubmission = this.handleFormSubmission.bind(this);
        this.formTarget.addEventListener('submit', this.handleFormSubmission);
    }

    disconnect() {
        this.formTarget.removeEventListener('submit', this.handleFormSubmission);
    }

    handleFormSubmission(event) {
        event.preventDefault();

        if (!confirm(this.confirmationMessageValue || 'Êtes-vous sûr de vouloir supprimer votre compte ? Cette action est irréversible.')) {
            return;
        }

        // Désactiver le bouton pour éviter les doubles soumissions
        this.submitButtonTarget.disabled = true;
        this.submitButtonTarget.textContent = 'Suppression en cours...';

        // Soumettre le formulaire
        this.formTarget.submit();
    }
}
