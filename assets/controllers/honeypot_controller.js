import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['honeypot'];
    static values = {
        hiddenClass: String
    };

    connect() {
        // Vérifier si les champs honeypot existent
        if (this.honeypotTargets.length === 0) {
            console.error('Honeypot fields not found');
            return;
        }

        // Masquer les champs honeypot
        this.honeypotTargets.forEach(field => {
            field.setAttribute('aria-hidden', 'true');
            field.setAttribute('tabindex', '-1');
        });
    }

    submit(event) {
        // Vérifier les champs honeypot
        const hasHoneypotContent = this.honeypotTargets.some(field => {
            const value = field.querySelector('input')?.value;
            return value && value.trim() !== '';
        });

        // Si un champ honeypot est rempli
        if (hasHoneypotContent) {
            event.preventDefault();
            event.stopPropagation();
            
            // Simuler un succès pour tromper les bots
            const fakeResponse = {
                success: true,
                message: 'Message envoyé avec succès'
            };
            
            // Attendre un délai aléatoire pour simuler un traitement
            setTimeout(() => {
                const customEvent = new CustomEvent('contact:success', {
                    detail: fakeResponse
                });
                this.element.dispatchEvent(customEvent);
            }, Math.random() * 1000 + 500);
            
            return true;
        }

        return false;
    }
}