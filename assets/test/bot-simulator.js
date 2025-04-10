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
        const formData = new FormData();
        
        // Ajouter tous les champs au FormData
        Object.entries(maliciousData).forEach(([key, value]) => {
            formData.append(key, value);
        });

        // Récupérer le token CSRF s'il existe
        const csrfToken = document.querySelector('input[name="_token"]');
        if (csrfToken) {
            formData.append('_token', csrfToken.value);
        }

        const response = await fetch('/contact', {
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

    // Champs normaux du formulaire
    formData.append('registration_form[email]', 'bot@evil.com');
    formData.append('registration_form[plainPassword][first]', '<script>alert(\'XSS\')</script>P@ssw0rd123');
    formData.append('registration_form[plainPassword][second]', '<script>alert(\'XSS\')</script>P@ssw0rd123');
    formData.append('registration_form[agreeTerms]', '1');

    // Tentative de remplir les champs honeypot
    formData.append('registration_form[honeypot_first_name]', 'John');
    formData.append('registration_form[honeypot_last_name]', 'Doe');

    // Tentative de bypass du timestamp
    formData.append('registration_form[_timestamp]', Math.floor(Date.now() / 1000) - 1);

    // Récupérer le token CSRF
    const csrfToken = document.querySelector('input[name="registration_form[_token]"]').value;
    formData.append('registration_form[_token]', csrfToken);

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

// Exécuter les attaques
async function runTests() {
    console.log('Début des tests de sécurité...');
    
    console.log('Test 1: Attaque du formulaire de contact');
    await simulateContactAttack();
    
    console.log('Test 2: Attaque du formulaire d\'inscription');
    await simulateRegistrationAttack();
    
    console.log('Tests terminés.');
}

runTests();
