// Fonction pour simuler une attaque sur le formulaire de contact
async function simulateContactAttack() {
    // Données malveillantes avec différents types d'attaques
    const maliciousData = {
        type: "professionnel",
        civilite: "monsieur",
        nom: "<script>alert('XSS')</script>",  // Test XSS
        prenom: "'; DROP TABLE users; --",      // Test SQL Injection
        email: "attacker@evil.com",
        telephone: "0612345678",
        localisation: "Paris",
        description: `
            ${document.cookie}                   
            <img src="x" onerror="alert('XSS')">
            javascript:alert('XSS')
        `,
        entreprise: "Fake Corp",
        // Tentative de remplir les champs honeypot
        contact_email: "spam@bot.com",
        mobile_phone: "0687654321",
        // Tentative de bypass du timestamp
        _timestamp: Math.floor(Date.now() / 1000) - 1,
    };

    // Envoi de la requête
    try {
        // Récupérer le token CSRF
        const csrfToken = document.querySelector('input[name="_token"][value]');
        if (!csrfToken) {
            console.warn('Token CSRF non trouvé dans le formulaire');
            return;
        }
        maliciousData._token = csrfToken.value;

        const response = await fetch('/contact/', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(maliciousData)
        });

        let result;
        const contentType = response.headers.get('content-type');
        if (contentType && contentType.includes('application/json')) {
            result = await response.json();
        } else {
            result = await response.text();
        }
        console.log('Résultat de l\'attaque sur le formulaire de contact:', {
            status: response.status,
            response: result
        });
    } catch (error) {
        console.error('Erreur:', error);
    }
}

// Fonction pour simuler une attaque sur le formulaire d'inscription
async function simulateRegistrationAttack() {
    // Données malveillantes pour l'inscription
    const formData = new FormData();

    // Champs normaux du formulaire avec un mot de passe valide mais malicieux
    formData.append('registration_form[email]', 'bot@evil.com');
    formData.append('registration_form[plainPassword][first]', 'Passw0rd!@#$%^&*');
    formData.append('registration_form[plainPassword][second]', 'Passw0rd!@#$%^&*');
    formData.append('registration_form[agreeTerms]', '1');

    // Tentative de remplir les champs honeypot
    formData.append('registration_form[honeypot_first_name]', 'John');
    formData.append('registration_form[honeypot_last_name]', 'Doe');

    // Tentative de bypass du timestamp
    formData.append('registration_form[_timestamp]', Math.floor(Date.now() / 1000) - 1);

    // Récupérer le token CSRF
    const csrfToken = document.querySelector('input[name="registration_form[_token]"]');
    if (!csrfToken) {
        console.warn('Token CSRF non trouvé dans le formulaire d\'inscription');
        return;
    }
    formData.append('registration_form[_token]', csrfToken.value);

    try {
        const response = await fetch('/inscription', {
            method: 'POST',
            body: formData
        });

        let result;
        const contentType = response.headers.get('content-type');
        if (contentType && contentType.includes('application/json')) {
            result = await response.json();
        } else {
            result = await response.text();
        }
        console.log('Résultat de l\'attaque sur le formulaire d\'inscription:', {
            status: response.status,
            response: result
        });
    } catch (error) {
        console.error('Erreur:', error);
    }
}

// Exécuter le test approprié
async function runTests() {
    console.log('Début des tests de sécurité...');
    
    // Déterminer quel formulaire tester en fonction de la page courante
    const path = window.location.pathname;
    
    if (path.startsWith('/contact')) {
        console.log('Test : Attaque du formulaire de contact');
        await simulateContactAttack();
    } else if (path.startsWith('/inscription')) {
        console.log('Test : Attaque du formulaire d\'inscription');
        await simulateRegistrationAttack();
    } else {
        console.log('Aucun test disponible pour cette page');
    }
    
    console.log('Tests terminés.');
}

runTests();
