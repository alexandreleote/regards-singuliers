import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['type', 'section', 'name', 'firstname', 'email', 'phone', 'location', 'company', 'description']
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
        const particularLabel = switchContainer.querySelector('label[for="type-particular"]');
        const professionalLabel = switchContainer.querySelector('label[for="type-professional"]');
        
        // Définir la largeur du background en fonction du label
        const updateBackgroundSize = () => {
            const activeLabel = this.element.querySelector('#type-professional').checked ? professionalLabel : particularLabel;
            const width = activeLabel.offsetWidth;
            const left = activeLabel.offsetLeft;
            
            switchBackground.style.width = `${width}px`;
            switchBackground.style.left = `${left}px`;
        };

        // Observer les changements de taille
        const resizeObserver = new ResizeObserver(() => {
            updateBackgroundSize();
        });

        resizeObserver.observe(particularLabel);
        resizeObserver.observe(professionalLabel);

        // Initialiser l'apparence
        updateBackgroundSize();

        // Initialiser les placeholders
        this.nameTarget.placeholder = 'Votre nom de famille';
        this.firstnameTarget.placeholder = 'Votre prénom';
        this.emailTarget.placeholder = 'Votre adresse email';
        this.phoneTarget.placeholder = 'Votre numéro de téléphone';
        this.locationTarget.placeholder = 'Votre ville';
        this.companyTarget.placeholder = 'Nom de votre entreprise';
        this.descriptionTarget.placeholder = 'Décrivez votre projet en quelques mots (style souhaité, budget, contraintes particulières...)';
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
        if (this.hasPhoneTarget) {
            this.phoneTarget.addEventListener('input', (e) => {
                // Supprimer tous les caractères non numériques
                let value = e.target.value.replace(/\D/g, '');
                
                // Limiter à 10 chiffres
                if (value.length > 10) {
                    value = value.slice(0, 10);
                }
                
                // Formater le numéro (XX XX XX XX XX)
                if (value.length > 0) {
                    value = value.match(/.{1,2}/g).join(' ');
                }

                e.target.value = value;
            });
        }
    }

    setupLocationAutocomplete() {
        if (this.hasLocationTarget) {
            let timeout;
            this.locationTarget.addEventListener('input', (e) => {
                clearTimeout(timeout);
                timeout = setTimeout(() => this.searchLocation(e.target.value), 300);
            });
        }
    }

    async searchLocation(query) {
        if (!query || query.length < 2) return;

        try {
            const response = await fetch(`https://geo.api.gouv.fr/communes?nom=${query}&boost=population&limit=5`);
            if (!response.ok) throw new Error('Erreur réseau');
            
            const data = await response.json();
            this.showLocationSuggestions(data);
        } catch (error) {
            console.error('Erreur lors de la recherche:', error);
        }
    }

    showLocationSuggestions(suggestions) {
        // Supprimer les anciennes suggestions
        const existingList = document.getElementById('location-suggestions');
        if (existingList) existingList.remove();

        if (suggestions.length === 0) return;

        // Créer la liste des suggestions
        const list = document.createElement('ul');
        list.id = 'location-suggestions';
        list.className = 'location-suggestions';

        suggestions.forEach(commune => {
            const li = document.createElement('li');
            const nomCommune = commune.nom;
            const departement = commune.codeDepartement;
            const region = commune.region ? commune.region.nom : '';
            
            li.innerHTML = `
                <strong>${nomCommune}</strong>
                <span class="text-muted">${departement}${region ? ` - ${region}` : ''}</span>
            `;
            
            li.addEventListener('click', () => {
                this.locationTarget.value = `${nomCommune} (${departement})`;
                list.remove();
            });
            list.appendChild(li);
        });

        // Insérer la liste après le champ de localisation
        this.locationTarget.parentNode.appendChild(list);

        // Gérer la fermeture des suggestions lors d'un clic à l'extérieur
        document.addEventListener('click', (e) => {
            if (!this.locationTarget.contains(e.target) && !list.contains(e.target)) {
                list.remove();
            }
        });
    }

    updateSwitchAppearance() {
        const switchBackground = this.element.querySelector('.switch-background');
        const professionalRadio = this.element.querySelector('#type-professional');
        const particularLabel = this.element.querySelector('label[for="type-particular"]');
        const professionalLabel = this.element.querySelector('label[for="type-professional"]');
        
        const activeLabel = professionalRadio.checked ? professionalLabel : particularLabel;
        const width = activeLabel.offsetWidth;
        const left = activeLabel.offsetLeft;
        
        switchBackground.style.width = `${width}px`;
        switchBackground.style.left = `${left}px`;
        
        // Mettre à jour les couleurs
        if (professionalRadio.checked) {
            professionalLabel.style.color = '#fff';
            particularLabel.style.color = '#666';
        } else {
            particularLabel.style.color = '#fff';
            professionalLabel.style.color = '#666';
        }
    }

    change(event) {
        this.updateSwitchAppearance();
        
        // Gérer l'affichage de la section entreprise
        const type = event.target.value;
        console.log('Type sélectionné:', type);
        
        if (type === 'professional') {
            this.sectionTarget.classList.remove(this.hiddenClass);
            this.companyTarget.required = true;
        } else {
            this.sectionTarget.classList.add(this.hiddenClass);
            this.companyTarget.required = false;
            this.companyTarget.value = '';
        }
    }

    // Gestion de la soumission du formulaire
    async submit(event) {
        event.preventDefault();
        
        const formData = new FormData(event.target);
        const submitButton = event.target.querySelector('button[type="submit"]');
        submitButton.disabled = true;
        
        try {
            const response = await fetch('/contact/submit', {
                method: 'POST',
                body: formData
            });
            
            if (!response.ok) {
                throw new Error('Erreur lors de l\'envoi du formulaire');
            }
            
            const result = await response.json();
            console.log('Réponse:', result);
            
            // Réinitialiser le formulaire
            event.target.reset();
            
            // Afficher un message de succès
            alert('Votre message a été envoyé avec succès !');
            
        } catch (error) {
            console.error('Erreur:', error);
            alert('Une erreur est survenue lors de l\'envoi du message. Veuillez réessayer.');
        } finally {
            submitButton.disabled = false;
        }
    }
} 