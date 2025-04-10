import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    connect() {
        console.log('HoneypotController connecté');
        this.element.addEventListener('submit', this.onSubmit.bind(this));
    }
    
    onSubmit(event) {
        // Vérifier les champs honeypot
        const honeypotFields = this.element.querySelectorAll('input[name="phone"], input[name="work_email"]');
        console.log('Vérification des champs honeypot:', honeypotFields.length);
        
        const hasHoneypotContent = Array.from(honeypotFields).some(field => {
            const hasValue = field.value && field.value.trim() !== '';
            if (hasValue) {
                console.log('Bot détecté ! Champ honeypot rempli:', field.name, field.value);
            }
            return hasValue;
        });
        
        if (hasHoneypotContent) {
            console.log('Blocage de la soumission du formulaire');
            event.preventDefault();
            event.stopPropagation();
            return false;
        }
        
        console.log('Soumission autorisée');
        return true;
    }
}