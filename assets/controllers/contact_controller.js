import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['type', 'section', 'email']
    static classes = ['hidden']

    connect() {
        // Le code ne s'exécute que si le contrôleur est connecté (présent sur la page)
        this.updateFormSections();
    }

    updateFormSections() {
        const selectedType = this.typeTargets.find(input => input.checked)?.value;
        if (!selectedType) return;
        
        // Gérer l'affichage de la section entreprise
        this.sectionTargets.forEach(section => {
            if (section.dataset.contactTypeTarget === selectedType) {
                section.classList.remove(this.hiddenClass);
            } else {
                section.classList.add(this.hiddenClass);
            }
        });

        // Mettre à jour le placeholder de l'email selon le type
        if (this.hasEmailTarget) {
            this.emailTarget.placeholder = selectedType === 'professional' ? 
                'exemple@entreprise.com' : 
                'exemple@email.com';
        }
    }

    // Méthode appelée lors du changement de type
    change() {
        this.updateFormSections();
    }

    // Gestion de la soumission du formulaire
    async submit(event) {
        event.preventDefault();
        
        try {
            const formData = new FormData(event.target);
            const data = Object.fromEntries(formData.entries());
            
            const response = await fetch('/contact/submit', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify(data)
            });
            
            if (!response.ok) {
                throw new Error(`Erreur HTTP: ${response.status}`);
            }

            const result = await response.json();
            
            if (result.success) {
                alert('Votre message a été envoyé avec succès !');
                event.target.reset();
                // Réinitialiser le formulaire à l'état initial (particulier)
                this.typeTargets.find(input => input.value === 'particular').checked = true;
                this.updateFormSections();
            } else {
                alert(result.error || 'Une erreur est survenue lors de l\'envoi du formulaire.');
            }
            
        } catch (error) {
            console.error('Erreur détaillée:', error);
            alert('Une erreur est survenue lors de l\'envoi du formulaire. Veuillez réessayer.');
        }
    }
} 