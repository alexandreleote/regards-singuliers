console.log('📝 Chargement du script de test d\'inscription...');

class RegistrationBot {
    constructor() {
        // Attendre que le DOM soit chargé
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => this.initialize());
        } else {
            this.initialize();
        }
    }

    initialize() {
        this.form = document.querySelector('form[name="registration_form"]');
        if (!this.form) {
            console.warn('Formulaire d\'inscription non trouvé');
            return;
        }
        console.log('🤖 Registration Bot initialisé');
        this.addTestButton();
    }

    addTestButton() {
        // Créer le bouton de test
        const button = document.createElement('button');
        button.type = 'button';
        button.textContent = 'Tester la protection anti-bot';
        button.style.cssText = 'position: fixed; top: 10px; right: 10px; z-index: 9999; padding: 10px; background: #4CAF50; color: white; border: none; border-radius: 4px; cursor: pointer;';
        
        // Ajouter le bouton à la page
        document.body.appendChild(button);
        
        // Ajouter l'écouteur d'événement
        button.addEventListener('click', () => this.runTest());
    }

    async runTest() {
        console.log('🔄 Début du test de sécurité du formulaire d\'inscription...');

        // Remplir les champs honeypot
        const honeypotFields = this.form.querySelectorAll('input[name="phone"], input[name="work_email"]');
        console.log('Champs honeypot trouvés:', honeypotFields.length);
        
        honeypotFields.forEach(field => {
            field.value = field.name === 'phone' ? '0612345678' : 'pro@entreprise.com';
            console.log('Champ honeypot rempli:', field.name, field.value);
        });
        
        // Remplir les champs normaux
        const email = this.form.querySelector('input[name="registration_form[email]"]');
        const password1 = this.form.querySelector('input[name="registration_form[plainPassword][first]"]');
        const password2 = this.form.querySelector('input[name="registration_form[plainPassword][second]"]');
        const terms = this.form.querySelector('input[name="registration_form[agreeTerms]"]');
        
        if (email) email.value = 'spambot@evil-domain.com';
        if (password1) password1.value = 'Str0ngP@ssw0rd123!';
        if (password2) password2.value = 'Str0ngP@ssw0rd123!';
        if (terms) terms.checked = true;

        try {
            console.log('📤 Tentative d\'inscription...');
            
            // Trouver le bouton submit du formulaire
            const submitButton = this.form.querySelector('button[type="submit"]');
            if (submitButton) {
                submitButton.click();
            } else {
                console.error('Bouton submit non trouvé');
            }
            
        } catch (error) {
            console.error('❌ Erreur lors du test:', error);
        }
    }
}

// Démarrer le test uniquement sur la page d'inscription
new RegistrationBot();
