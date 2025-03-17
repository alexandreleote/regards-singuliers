import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['type', 'section', 'civilite', 'nom', 'prenom', 'email', 'telephone', 'localisation', 'entreprise', 'description']
    static classes = ['hidden']

    connect() {
        // Créer l'élément de fond
        const switchContainer = this.element.querySelector('.switch-container');
        const switchBackground = document.createElement('span');
        switchBackground.classList.add('switch-background');
        switchContainer.appendChild(switchBackground);
        
        // Appliquer les styles initiaux
        switchBackground.style.position = 'absolute';
        switchBackground.style.height = 'calc(100% - 8px)';
        switchBackground.style.backgroundColor = '#007bff';
        switchBackground.style.borderRadius = '25px';
        switchBackground.style.top = '4px';
        switchBackground.style.transition = 'all 0.3s cubic-bezier(0.4, 0, 0.2, 1)';
        switchBackground.style.zIndex = '1';

        // Calculer la largeur exacte pour chaque label
        const particulierLabel = switchContainer.querySelector('label[for="type-particulier"]');
        const professionnelLabel = switchContainer.querySelector('label[for="type-professionnel"]');
        
        // Initialiser les couleurs des labels
        particulierLabel.style.color = '#fff';
        professionnelLabel.style.color = '#666';
        
        // Définir la largeur du background en fonction du label
        const updateBackgroundSize = () => {
            const activeLabel = this.element.querySelector('#type-professionnel').checked ? professionnelLabel : particulierLabel;
            const width = activeLabel.offsetWidth;
            const left = activeLabel.offsetLeft;
            
            switchBackground.style.width = `${width}px`;
            switchBackground.style.left = `${left}px`;
        };

        // Observer les changements de taille
        const resizeObserver = new ResizeObserver(() => {
            updateBackgroundSize();
        });

        resizeObserver.observe(particulierLabel);
        resizeObserver.observe(professionnelLabel);

        // Initialiser l'apparence
        updateBackgroundSize();

        // Initialiser les placeholders
        this.nomTarget.placeholder = 'Votre nom de famille';
        this.prenomTarget.placeholder = 'Votre prénom';
        this.emailTarget.placeholder = 'Votre adresse email';
        this.telephoneTarget.placeholder = 'Votre numéro de téléphone';
        this.localisationTarget.placeholder = 'Votre ville';
        this.entrepriseTarget.placeholder = 'Nom de votre entreprise';
        this.descriptionTarget.placeholder = 'Décrivez votre projet en quelques mots (style souhaité, budget, contraintes particulières...)';

        // Initialiser les gestionnaires d'événements
        this.setupPhoneInput();
        this.setupLocationAutocomplete();
    }

    clearErrors() {
        // Supprimer tous les messages d'erreur existants
        document.querySelectorAll('.error-message').forEach(el => el.remove());
        document.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
    }

    showError(field, message) {
        const input = document.querySelector(`[name="${field}"]`);
        if (!input) return;

        input.classList.add('is-invalid');
        const errorDiv = document.createElement('div');
        errorDiv.className = 'error-message invalid-feedback';
        errorDiv.textContent = message;
        input.parentNode.appendChild(errorDiv);
    }

    setupPhoneInput() {
        if (this.hasTelephoneTarget) {
            this.telephoneTarget.addEventListener('input', (e) => {
                // Supprimer tous les caractères non numériques
                let value = e.target.value.replace(/\D/g, '');
                
                // Limiter à 10 chiffres
                if (value.length > 10) {
                    value = value.slice(0, 10);
                }
                
                // Formater avec des espaces tous les 2 chiffres
                value = value.replace(/(\d{2})/g, '$1 ').trim();
                
                // Mettre à jour la valeur
                e.target.value = value;
            });

            // Nettoyer les espaces avant la soumission
            this.telephoneTarget.addEventListener('blur', (e) => {
                e.target.value = e.target.value.replace(/\s/g, '');
            });
        }
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
        if (!query) return;

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

    updateSwitchAppearance() {
        const switchBackground = this.element.querySelector('.switch-background');
        const professionnelRadio = this.element.querySelector('#type-professionnel');
        const particulierLabel = this.element.querySelector('label[for="type-particulier"]');
        const professionnelLabel = this.element.querySelector('label[for="type-professionnel"]');
        
        const activeLabel = professionnelRadio.checked ? professionnelLabel : particulierLabel;
        const width = activeLabel.offsetWidth;
        const left = activeLabel.offsetLeft;
        
        switchBackground.style.width = `${width}px`;
        switchBackground.style.left = `${left}px`;
        
        // Mettre à jour les couleurs
        if (professionnelRadio.checked) {
            professionnelLabel.style.color = '#fff';
            particulierLabel.style.color = '#666';
        } else {
            particulierLabel.style.color = '#fff';
            professionnelLabel.style.color = '#666';
        }
    }

    change(event) {
        this.updateSwitchAppearance();
        
        // Gérer l'affichage de la section entreprise
        const type = event.target.value;
        console.log('Type sélectionné:', type);
        
        if (type === 'professionnel') {
            this.sectionTarget.classList.remove(this.hiddenClass);
            this.entrepriseTarget.required = true;
        } else {
            this.sectionTarget.classList.add(this.hiddenClass);
            this.entrepriseTarget.required = false;
            this.entrepriseTarget.value = '';
        }
    }

    // Gestion de la soumission du formulaire
    async submit(event) {
        event.preventDefault();
        this.clearErrors();
        
        const formData = new FormData(event.target);
        const submitButton = event.target.querySelector('button[type="submit"]');
        submitButton.disabled = true;
        
        try {
            const data = {};
            formData.forEach((value, key) => {
                data[key] = value;
            });

            const response = await fetch('/contact/submit', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(data)
            });
            
            const result = await response.json();
            
            if (!response.ok) {
                if (result.errors) {
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
            alert(error.message);
        } finally {
            submitButton.disabled = false;
        }
    }
} 