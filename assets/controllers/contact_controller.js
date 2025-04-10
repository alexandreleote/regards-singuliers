import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['form', 'type', 'section', 'entreprise', 'civilite', 'nom', 'prenom', 'email', 'telephone', 'localisation', 'description'];
      
    static values = {
        hiddenClass: String
    }

    connect() {
        this.prenomTarget.placeholder = 'Votre prénom';
        this.nomTarget.placeholder = 'Votre nom';
        this.emailTarget.placeholder = 'Votre adresse@email.fr';
        this.telephoneTarget.placeholder = 'Votre numéro de téléphone';
        this.localisationTarget.placeholder = 'Votre ville';
        this.entrepriseTarget.placeholder = 'Nom de votre entreprise';
        this.descriptionTarget.placeholder = 'Décrivez votre projet';

        this.prenomTarget.setAttribute('aria-label', 'Votre prénom');
        this.nomTarget.setAttribute('aria-label', 'Votre nom de famille');
        this.emailTarget.setAttribute('aria-label', 'Votre adresse email');
        this.telephoneTarget.setAttribute('aria-label', 'Votre numéro de téléphone');
        this.localisationTarget.setAttribute('aria-label', 'Votre ville');
        this.entrepriseTarget.setAttribute('aria-label', 'Nom de votre entreprise');
        this.descriptionTarget.setAttribute('aria-label', 'Description de votre projet');

        this.setupPhoneInput();
        this.setupLocationAutocomplete();

        const isProfessionnel = this.typeTargets.find(target => target.checked)?.value === 'professionnel';
        this.toggleProfessionnelSection(isProfessionnel);

        this.formTarget.addEventListener('submit', this.submit.bind(this));
    }

    change(event) {
        const isProfessionnel = event.target.value === 'professionnel';
        this.toggleProfessionnelSection(isProfessionnel);
    }

    toggleProfessionnelSection(isProfessionnel) {
        if (isProfessionnel) {
            this.sectionTarget.classList.remove('hidden');
            this.entrepriseTarget.required = true;
            this.entrepriseTarget.setAttribute('aria-required', 'true');
        } else {
            this.sectionTarget.classList.add('hidden');
            this.entrepriseTarget.required = false;
            this.entrepriseTarget.removeAttribute('aria-required');
            this.entrepriseTarget.value = '';
        }
    }

    setupPhoneInput() {
        this.telephoneTarget.addEventListener('input', (e) => {
            let value = e.target.value.replace(/\D/g, '');
            value = value.substring(0, 10);
            
            if (value.length > 0) {
                value = value.match(/.{1,2}/g).join(' ');
            }
            
            e.target.value = value;

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
                timeout = setTimeout(() => {
                    const query = e.target.value;
                    if (query.length >= 3) {
                        this.searchLocation(query);
                    }
                }, 300);
            });
        }
    }

    async searchLocation(query) {
        if (!query || query.length < 3) return;

        try {
            const response = await fetch(`https://api-adresse.data.gouv.fr/search/?q=${encodeURIComponent(query)}&limit=5&type=municipality`, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                },
                mode: 'cors',
                credentials: 'omit'
            });
            
            if (!response.ok) {
                throw new Error('Erreur lors de la recherche de localisation');
            }
            
            const data = await response.json();
            
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
        
        const existingError = input.parentNode.querySelector('.error-message');
        if (existingError) {
            existingError.remove();
        }
        
        input.parentNode.appendChild(errorDiv);
    }

    async submit(event) {
        event.preventDefault();
        this.clearErrors();
        
        // Vérification des champs honeypot
        const honeypotFields = this.formTarget.querySelectorAll('.honeypot-field input');
        const timestamp = parseInt(this.formTarget.querySelector('input[name="_timestamp"]').value);
        const currentTime = Math.floor(Date.now() / 1000);
        const submissionTime = currentTime - timestamp;

        // Vérifier le temps de soumission (minimum 2 secondes, maximum 1 heure)
        if (submissionTime < 2 || submissionTime > 3600) {
            this.showErrorMessage('Message non envoyé');
            return;
        }

        // Vérifier si les champs honeypot sont remplis
        for (const field of honeypotFields) {
            if (field.value.trim() !== '') {
                this.showErrorMessage('Message non envoyé');
                return;
            }
        }
        
        if (!this.formTarget.checkValidity()) {
            this.formTarget.reportValidity();
            return;
        }

        const submitButton = this.formTarget.querySelector('button[type="submit"]');
        submitButton.disabled = true;

        try {
            const formData = new FormData(this.formTarget);
            // Filtrer les champs honeypot et le timestamp
            // Liste des champs attendus par le serveur
            const expectedFields = ['type', 'civilite', 'nom', 'prenom', 'email', 'telephone', 'localisation', 'description'];
            const data = {};
            
            // Ajouter uniquement les champs attendus
            for (const field of expectedFields) {
                const value = formData.get(field);
                if (value !== null) {
                    data[field] = field === 'telephone' ? value.replace(/\s+/g, '') : value;
                }
            }
            
            // Ajouter le champ entreprise seulement s'il est requis
            if (this.typeTargets.find(target => target.checked)?.value === 'professionnel') {
                data['entreprise'] = formData.get('entreprise') || '';  // Envoyer une chaîne vide si non rempli
            }

            const csrfToken = document.querySelector('input[name="_token"]').value;
            
            const response = await fetch('/contact/', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify(data)
            });
            
            const result = await response.json();
            
            if (!response.ok) {
                console.log('Réponse complète:', result);
                if (result.errors) {
                    this.showErrors(result.errors);
                } else {
                    this.showErrorMessage(result.message || 'Une erreur est survenue');
                }
                return;
            }
            
            this.formTarget.reset();
            this.showSuccessMessage();
            
        } catch (error) {
            console.error('Erreur:', error);
            this.showErrorMessage('Une erreur est survenue lors de l\'envoi du formulaire');
        } finally {
            submitButton.disabled = false;
        }
    }

    sanitizeInput(value) {
        if (typeof value !== 'string') return value;
        
        return value
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#x27;')
            .replace(/\//g, '&#x2F;');
    }

    showSuccessMessage() {
        const alert = document.createElement('div');
        alert.className = 'alert alert-success';
        alert.textContent = 'Votre message a été envoyé avec succès.';
        this.formTarget.insertAdjacentElement('beforebegin', alert);
        setTimeout(() => alert.remove(), 5000);
    }

    showErrorMessage(message = 'Une erreur est survenue') {
        const alert = document.createElement('div');
        alert.className = 'alert alert-danger';
        alert.textContent = message;
        this.formTarget.insertAdjacentElement('beforebegin', alert);
        setTimeout(() => alert.remove(), 5000);
    }

    showErrors(errors) {
        this.formTarget.querySelectorAll('.error-message').forEach(el => el.remove());
        this.formTarget.querySelectorAll('.form-control.is-invalid').forEach(el => el.classList.remove('is-invalid'));

        errors.forEach(error => {
            const field = this.formTarget.querySelector(`[name="${error.field}"]`);
            if (field) {
                field.classList.add('is-invalid');
                const errorDiv = document.createElement('div');
                errorDiv.className = 'error-message';
                errorDiv.textContent = error.message;
                field.parentNode.appendChild(errorDiv);
            }
        });
    }
}