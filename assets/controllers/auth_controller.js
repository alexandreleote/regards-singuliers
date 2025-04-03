import { Controller } from '@hotwired/stimulus';
import '../styles/auth.css';

export default class extends Controller {
    static targets = ['form', 'submitButton', 'messageElement']
    static values = {
        type: String // 'login', 'register', 'forgot-password'
    }

    connect() {
        if (this.hasFormTarget) {
            this.formTarget.addEventListener('submit', this.handleSubmit.bind(this));
        }
    }

    async handleSubmit(event) {
        event.preventDefault();
        const formData = new FormData(this.formTarget);
        
        if (this.hasSubmitButtonTarget) {
            this.submitButtonTarget.disabled = true;
        }

        try {
            const response = await fetch(this.formTarget.action, {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (response.ok) {
                if (data.redirect) {
                    window.location.href = data.redirect;
                } else {
                    this.showMessage('Opération réussie', 'success');
                }
            } else {
                this.showMessage(data.message || 'Une erreur est survenue', 'error');
            }
        } catch (error) {
            console.error('Erreur:', error);
            this.showMessage('Une erreur est survenue', 'error');
        } finally {
            if (this.hasSubmitButtonTarget) {
                this.submitButtonTarget.disabled = false;
            }
        }
    }

    showMessage(message, type = 'info') {
        if (this.hasMessageElementTarget) {
            this.messageElementTarget.textContent = message;
            this.messageElementTarget.className = `message message-${type}`;
            this.messageElementTarget.classList.remove('hidden');
            
            setTimeout(() => {
                this.messageElementTarget.classList.add('hidden');
            }, 5000);
        }
    }

    disconnect() {
        if (this.hasFormTarget) {
            this.formTarget.removeEventListener('submit', this.handleSubmit.bind(this));
        }
    }
} 