import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['type', 'section', 'email', 'phone', 'location', 'company']
    static classes = ['hidden']

    connect() {
        // Le code ne s'exécute que si le contrôleur est connecté (présent sur la page)
        this.updateFormSections();
        this.setupPhoneInput();
        this.setupLocationAutocomplete();
        this.clearErrors();
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
        if (!query || query.length < 3) return;

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
            li.textContent = `${commune.nom} (${commune.codeDepartement})`;
            li.addEventListener('click', () => {
                this.locationTarget.value = `${commune.nom} (${commune.codeDepartement})`;
                list.remove();
            });
            list.appendChild(li);
        });

        // Insérer la liste après le champ de localisation
        this.locationTarget.parentNode.appendChild(list);
    }

    updateFormSections() {
        const selectedType = this.typeTargets.find(input => input.checked)?.value;
        if (!selectedType) return;

        console.log('Type sélectionné:', selectedType);
        
        this.sectionTargets.forEach(section => {
            const targetType = section.dataset.contactTypeTarget;
            console.log('Section type:', targetType);
            
            if (targetType === selectedType) {
                section.classList.remove(this.hiddenClass);
                if (this.hasCompanyTarget && targetType === 'professional') {
                    this.companyTarget.required = true;
                }
            } else {
                section.classList.add(this.hiddenClass);
                if (this.hasCompanyTarget && targetType === 'professional') {
                    this.companyTarget.required = false;
                    this.companyTarget.value = ''; // Réinitialiser la valeur
                }
            }
        });

        if (this.hasEmailTarget) {
            this.emailTarget.placeholder = selectedType === 'professional' ? 
                'exemple@entreprise.com' : 
                'exemple@email.com';
        }
    }

    change(event) {
        console.log('Changement de type:', event.target.value);
        this.updateFormSections();
    }

    // Gestion de la soumission du formulaire
    async submit(event) {
        event.preventDefault();
        this.clearErrors();
        
        try {
            const formData = new FormData(event.target);
            const data = Object.fromEntries(formData.entries());
            
            // Log des données avant l'envoi
            console.log('Données à envoyer:', data);
            
            // Utiliser l'URL absolue
            const response = await fetch(window.location.origin + '/contact/submit', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify(data)
            });
            
            const result = await response.json();
            console.log('Réponse du serveur:', result);
            
            if (response.ok && result.success) {
                alert('Votre message a été envoyé avec succès !');
                event.target.reset();
                this.typeTargets.find(input => input.value === 'particular').checked = true;
                this.updateFormSections();
            } else {
                if (result.errors) {
                    Object.entries(result.errors).forEach(([field, message]) => {
                        this.showError(field, message);
                    });
                } else {
                    alert(result.error || 'Une erreur est survenue lors de l\'envoi du formulaire.');
                }
            }
            
        } catch (error) {
            console.error('Erreur détaillée:', error);
            alert('Une erreur est survenue lors de l\'envoi du formulaire. Veuillez réessayer.');
        }
    }
} 