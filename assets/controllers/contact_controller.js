import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    // Définition des targets (éléments du DOM ciblés) pour le contrôleur
    static targets = ['form', 'type', 'section', 'entreprise', 'civilite', 'nom', 'prenom', 'email', 'telephone', 'localisation', 'message'];
      
    // Valeurs personnalisées du contrôleur (ici une classe CSS pour masquer des éléments)
    static values = {
        hiddenClass: String
    }

    connect() {
        // Initialiser les placeholders pour améliorer l'UX
        this.prenomTarget.placeholder = 'Votre prénom';
        this.nomTarget.placeholder = 'Votre nom';
        this.emailTarget.placeholder = 'Votre adresse@email.fr';
        this.telephoneTarget.placeholder = 'Votre numéro de téléphone';
        this.localisationTarget.placeholder = 'Votre ville';
        this.entrepriseTarget.placeholder = 'Nom de votre entreprise';
        this.messageTarget.placeholder = 'Votre message';

        // Initialiser les aria-labels pour l'accessibilité
        this.prenomTarget.setAttribute('aria-label', 'Votre prénom');
        this.nomTarget.setAttribute('aria-label', 'Votre nom de famille');
        this.emailTarget.setAttribute('aria-label', 'Votre adresse email');
        this.telephoneTarget.setAttribute('aria-label', 'Votre numéro de téléphone');
        this.localisationTarget.setAttribute('aria-label', 'Votre ville');
        this.entrepriseTarget.setAttribute('aria-label', 'Nom de votre entreprise');
        this.messageTarget.setAttribute('aria-label', 'Votre message');

        // Initialiser les gestionnaires d'événements
        this.setupPhoneInput(); // Validation et formatage du numéro de téléphone
        this.setupLocationAutocomplete(); // Autocomplétion pour la localisation

        // Gestion de l'éatat initial du formaulaire (particulier/professionnel)
        const isProfessionnel = this.typeTargets.find(target => target.checked)?.value === 'professionnel';
        this.toggleProfessionnelSection(isProfessionnel);

        this.formTarget.addEventListener('submit', this.handleSubmit.bind(this));
    }

    // Gérer le changement de la nature du contact (particulier ou professionnel)
    change(event) {
        const type = event.target.value;
        if (type === 'professionnel') {
            this.sectionTarget.classList.remove(this.hiddenClass);
        } else {
            this.sectionTarget.classList.add(this.hiddenClass);
        }
    }

    // Affiche/masque la section entreprise selon le type de contact
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

    // Configuration de la validation et du formatage du numéro de téléphone
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

    // Configuration de l'autocomplétion pour la localisation
    setupLocationAutocomplete() {
        if (this.hasLocalisationTarget) {
            let timeout;
            this.localisationTarget.addEventListener('input', (e) => {
                // Recherche différée pour éviter trop d'appels API
                clearTimeout(timeout);
                timeout = setTimeout(() => this.searchLocation(e.target.value), 300);
            });
        }
    }

    // Recherche de localités via L'API gouv.fr
    async searchLocation(query) {
        if (!query || query.length < 3) return;

        try {
            // Appel à l'API d'adresse pour obtenir des suggestions
            const response = await fetch(`https://api-adresse.data.gouv.fr/search/?q=${encodeURIComponent(query)}&limit=5`);
            if (!response.ok) {
                throw new Error('Erreur lors de la recherche de localisation');
            }
            
            const data = await response.json();
            
            // Supprimer les anciennes suggestions
            const oldSuggestions = document.querySelector('.location-suggestions');
            if (oldSuggestions) {
                oldSuggestions.remove();
            }

            // Création de la liste de suggestions
            if (data.features && data.features.length > 0) {
                const suggestions = document.createElement('ul');
                suggestions.className = 'location-suggestions';
                
                data.features.forEach(feature => {
                    const li = document.createElement('li');
                    const city = feature.properties.city;
                    const postcode = feature.properties.postcode;
                    const context = feature.properties.context;
                    
                    // Création des éléments de suggestion
                    li.innerHTML = `
                        <strong>${city}</strong>
                        <span class="text-muted">${postcode} - ${context}</span>
                    `;
                    
                    // Gestion du clic sur une suggestion
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

    // Affichage d'un message d'erreur pour un champ spécifique
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

    // Gestion de la soumission du formulaire
    async handleSubmit(event) {
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
            
            // Préparation des données du formulaire
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

            // Récupérer le token CSRF
            const token = event.target.querySelector('input[name="_token"]').value;

            // Envoi des données au serveur
            const response = await fetch('/contact/submit', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-Token': token
                },
                body: JSON.stringify(data)
            });
            
            const result = await response.json();
            
            // Gestion des réponses du serveur
            if (!response.ok) {
                if (result.error) {
                    throw new Error(result.error);
                }
                throw new Error('Une erreur est survenue lors de l\'envoi du formulaire');
            }
            
            // Réinitialiser le formulaire
            event.target.reset();
            
            // Rediriger vers la page de confirmation
            window.location.href = '/contact/confirmation';
            
        } catch (error) {
            console.error('Erreur:', error);
            alert(error.message);
        } finally {
            submitButton.disabled = false;
        }
    }

    // Nettoyage et sécurisation des entrées utilisateur
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

    showSuccessMessage() {
        const alert = document.createElement('div');
        alert.className = 'alert alert-success';
        alert.textContent = 'Votre message a été envoyé avec succès.';
        this.formTarget.insertAdjacentElement('beforebegin', alert);
        setTimeout(() => alert.remove(), 5000);
    }

    showErrorMessage() {
        const alert = document.createElement('div');
        alert.className = 'alert alert-danger';
        alert.textContent = 'Une erreur est survenue lors de l\'envoi du message.';
        this.formTarget.insertAdjacentElement('beforebegin', alert);
        setTimeout(() => alert.remove(), 5000);
    }

    showErrors(errors) {
        // Supprimer les messages d'erreur existants
        this.formTarget.querySelectorAll('.error-message').forEach(el => el.remove());
        this.formTarget.querySelectorAll('.form-control.is-invalid').forEach(el => el.classList.remove('is-invalid'));

        // Afficher les nouveaux messages d'erreur
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