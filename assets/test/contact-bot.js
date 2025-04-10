/**
 * Script de test simulant un robot qui tente de remplir le formulaire de contact
 */
class ContactBot {
    constructor() {
        this.form = document.querySelector('form.contact-form');
        if (!this.form) {
            console.warn('Formulaire de contact non trouvé');
            return;
        }
        this.init();
    }

    init() {
        console.log('🤖 Contact Bot initialisé');
        this.runTest();
    }

    async runTest() {
        console.log('🔄 Début du test de sécurité du formulaire de contact...');

        // Données malveillantes pour le test
        const maliciousData = {
            'contact_form[type]': 'particulier',
            'contact_form[civilite]': 'monsieur',
            'contact_form[prenom]': 'SpamBot',
            'contact_form[nom]': 'Malicious',
            'contact_form[email]': 'spam@evil-domain.com',
            'contact_form[telephone]': '0611223344',
            'contact_form[localisation]': 'Evil City',
            'contact_form[description]': 'Message automatisé de spam'
        };

        // Remplir les champs honeypot avec des valeurs plausibles
        maliciousData['work_email'] = 'contact@entreprise.com';
        maliciousData['mobile_phone'] = '0612345678';
        console.log('🍯 Champs honeypot remplis avec des valeurs plausibles');

        // Ajouter un timestamp modifié pour simuler une soumission trop rapide
        maliciousData['_timestamp'] = Math.floor(Date.now() / 1000) - 1;

        try {
            console.log('📤 Tentative d\'envoi du formulaire...');
            // Récupérer le token CSRF
            const csrfToken = this.form.querySelector('input[name="_token"]').value;
            const response = await fetch(this.form.action, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify(maliciousData)
            });

            if (response.status === 403) {
                console.log('✅ Test réussi : La protection anti-bot a bien bloqué la soumission');
                this.logProtectionDetails();
            } else {
                console.error('❌ Test échoué : La soumission n\'a pas été bloquée !');
                console.log(`📝 Status de la réponse : ${response.status}`);
            }
        } catch (error) {
            console.error('❌ Erreur lors du test :', error);
        }
    }

    logProtectionDetails() {
        const honeypotDiv = this.form.querySelector('[data-honeypot-target="honeypot"]');
        if (honeypotDiv) {
            const honeypotFields = Array.from(honeypotDiv.querySelectorAll('input')).map(input => input.name);
            console.log('🛡️ Protection détectée :', {
                'Champs honeypot': honeypotFields,
                'Protection temporelle': true
            });
        }
    }
}

// Démarrer le test uniquement sur la page de contact
if (document.querySelector('form.contact-form')) {
    console.log('📝 Page de contact détectée');
    new ContactBot();
}
