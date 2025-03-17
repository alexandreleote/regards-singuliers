import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['type', 'section', 'entreprise', 'civilite', 'nom', 'prenom', 'email', 'telephone', 'localisation', 'description'];
    static values = {
        hiddenClass: String
    }

    connect() {
        // Initialiser les placeholders
        this.nomTarget.placeholder = 'Votre nom';
        this.prenomTarget.placeholder = 'Votre prénom';
        this.emailTarget.placeholder = 'Votre adresse email';
        this.telephoneTarget.placeholder = 'Votre numéro de téléphone';
        this.localisationTarget.placeholder = 'Votre ville';
        this.entrepriseTarget.placeholder = 'Nom de votre entreprise';
        this.descriptionTarget.placeholder = 'Décrivez votre projet en quelques mots (style souhaité, budget, contraintes particulières...)';

        // Initialiser les gestionnaires d'événements
        this.setupPhoneInput();
        this.setupLocationAutocomplete();

        // Vérifier l'état initial du type
        const isProfessionnel = this.typeTargets.find(target => target.checked)?.value === 'professionnel';
        this.toggleProfessionnelSection(isProfessionnel);
    }

    change(event) {
        const isProfessionnel = event.target.value === 'professionnel';
        this.toggleProfessionnelSection(isProfessionnel);
    }

    toggleProfessionnelSection(isProfessionnel) {
        if (isProfessionnel) {
            this.sectionTarget.classList.remove('hidden');
            this.entrepriseTarget.required = true;
        } else {
            this.sectionTarget.classList.add('hidden');
            this.entrepriseTarget.required = false;
            this.entrepriseTarget.value = ''; // Réinitialiser la valeur
        }
    }

    setupPhoneInput() {
        this.telephoneTarget.addEventListener('input', (e) => {
            // Supprimer tous les caractères non numériques
            let value = e.target.value.replace(/\D/g, '');
            
            // Limiter à 10 chiffres
            value = value.substring(0, 10);
            
            // Formater le numéro (XX XX XX XX XX)
            if (value.length > 0) {
                value = value.match(/.{1,2}/g).join(' ');
            }
            
            e.target.value = value;

            // Vérifier la validité
            const numericValue = value.replace(/\s/g, '');
            if (numericValue.length === 10) {
                e.target.setCustomValidity('');
            } else {
                e.target.setCustomValidity('Le numéro doit contenir exactement 10 chiffres');
            }
        });
    }

    setupLocationAutocomplete() {
        if (this.hasLocalisationTarget) {
            let timeout;
            this.localisationTarget.addEventListener('input', (e) => {
                clearTimeout(timeout);
                timeout = setTimeout(() => this.searchLocation(e.target.value), 300);
            });
        }
    }

    async searchLocation(query) {
        if (!query || query.length < 3) return;

        try {
            const response = await fetch(`https://api-adresse.data.gouv.fr/search/?q=${encodeURIComponent(query)}&limit=5`);
            const data = await response.json();
            
            // Supprimer les anciennes suggestions
            const oldSuggestions = document.querySelector('.location-suggestions');
            if (oldSuggestions) {
                oldSuggestions.remove();
            }
            
            if (data.features && data.features.length > 0) {
                const suggestions = document.createElement('ul');
                suggestions.className = 'location-suggestions';
                
                data.features.forEach(feature => {
                    const li = document.createElement('li');
                    const city = feature.properties.city;
                    const postcode = feature.properties.postcode;
                    const context = feature.properties.context;
                    
                    li.innerHTML = `
                        <strong>${city}</strong>
                        <span class="text-muted">${postcode} - ${context}</span>
                    `;
                    
                    li.addEventListener('click', () => {
                        this.localisationTarget.value = `${city}, ${postcode} - ${context}`;
                        suggestions.remove();
                    });
                    
                    suggestions.appendChild(li);
                });
                
                this.localisationTarget.parentNode.appendChild(suggestions);
            }
        } catch (error) {
            console.error('Erreur lors de la recherche de localisation:', error);
        }
    }

    clearErrors() {
        // Supprimer tous les messages d'erreur
        document.querySelectorAll('.error-message').forEach(el => el.remove());
        document.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
    }

    showError(field, message) {
        const input = this.element.querySelector(`[name="${field}"]`);
        if (!input) return;

        input.classList.add('is-invalid');
        
        const errorDiv = document.createElement('div');
        errorDiv.className = 'error-message';
        errorDiv.textContent = message;
        
        // Supprimer l'ancien message d'erreur s'il existe
        const existingError = input.parentNode.querySelector('.error-message');
        if (existingError) {
            existingError.remove();
        }
        
        input.parentNode.appendChild(errorDiv);
    }

    async submit(event) {
        event.preventDefault();
        this.clearErrors();
        
        // Vérifier la validité du formulaire
        if (!event.target.checkValidity()) {
            event.target.reportValidity();
            return;
        }
        
        const formData = new FormData(event.target);
        const submitButton = event.target.querySelector('button[type="submit"]');
        submitButton.disabled = true;
        
        try {
            const data = {};
            const csrfToken = formData.get('_token');
            
            formData.forEach((value, key) => {
                if (key !== '_token') {
                    // Pour le téléphone, supprimer les espaces
                    if (key === 'telephone') {
                        data[key] = value.replace(/\s/g, '');
                    } else {
                        data[key] = this.sanitizeInput(value);
                    }
                }
            });

            // Ne pas inclure le champ entreprise si on est en mode particulier
            if (data.type !== 'professionnel') {
                delete data.entreprise;
            }

            const response = await fetch('/contact/submit', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify(data)
            });
            
            if (!response.ok) {
                const result = await response.json();
                if (result.errors) {
                    console.log('Erreurs de validation:', result.errors);
                    Object.entries(result.errors).forEach(([field, message]) => {
                        this.showError(field, message);
                    });
                    throw new Error('Veuillez corriger les erreurs dans le formulaire');
                }
                throw new Error(result.error || 'Erreur lors de l\'envoi du formulaire');
            }
            
            // Réinitialiser le formulaire
            event.target.reset();
            
            // Rediriger vers la page de confirmation
            window.location.href = '/contact/confirmation';
            
        } catch (error) {
            console.error('Erreur:', error);
            if (error.message !== 'Veuillez corriger les erreurs dans le formulaire') {
                alert(error.message);
            }
        } finally {
            submitButton.disabled = false;
        }
    }

    sanitizeInput(value) {
        if (typeof value !== 'string') return value;
        
        // Convertir les caractères spéciaux en entités HTML
        return value
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#x27;')
            .replace(/\//g, '&#x2F;');
    }
}