import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['mobilePhone', 'workEmail']
    static values = {
        hiddenClass: String
    }

    connect() {
        // S'assurer que les champs sont bien cachés
        this.hideHoneypotFields();
        
        // Désactiver complètement les interactions
        this.disableHoneypotFields();
        
        // Ajouter des écouteurs d'événements pour la validation
        this.setupValidation();
    }

    hideHoneypotFields() {
        // Ajouter des classes CSS pour cacher les champs
        this.mobilePhoneTarget.classList.add(this.hiddenClassValue);
        this.workEmailTarget.classList.add(this.hiddenClassValue);
        
        // S'assurer que les champs sont invisibles
        this.mobilePhoneTarget.style.display = 'none';
        this.workEmailTarget.style.display = 'none';
    }

    disableHoneypotFields() {
        // Désactiver l'autocomplétion
        this.mobilePhoneTarget.setAttribute('autocomplete', 'off');
        this.workEmailTarget.setAttribute('autocomplete', 'off');
        
        // Désactiver la tabulation
        this.mobilePhoneTarget.setAttribute('tabindex', '-1');
        this.workEmailTarget.setAttribute('tabindex', '-1');
        
        // Désactiver les interactions souris
        this.mobilePhoneTarget.style.pointerEvents = 'none';
        this.workEmailTarget.style.pointerEvents = 'none';
        
        // Désactiver la sélection de texte
        this.mobilePhoneTarget.style.userSelect = 'none';
        this.workEmailTarget.style.userSelect = 'none';
    }

    setupValidation() {
        // Écouter les changements sur les champs honeypot
        this.mobilePhoneTarget.addEventListener('input', this.validateHoneypot.bind(this));
        this.workEmailTarget.addEventListener('input', this.validateHoneypot.bind(this));
        
        // Écouter les changements de focus
        this.mobilePhoneTarget.addEventListener('focus', this.handleFocus.bind(this));
        this.workEmailTarget.addEventListener('focus', this.handleFocus.bind(this));
    }

    validateHoneypot(event) {
        // Si un champ honeypot est modifié, bloquer le formulaire
        if (this.mobilePhoneTarget.value !== '' || this.workEmailTarget.value !== '') {
            this.blockForm();
        }
    }

    handleFocus(event) {
        // Si un champ honeypot reçoit le focus, bloquer le formulaire
        this.blockForm();
    }

    blockForm() {
        // Réinitialiser les champs
        this.mobilePhoneTarget.value = '';
        this.workEmailTarget.value = '';
        
        // Réinitialiser le formulaire
        this.element.reset();
        
        // Afficher un message
        alert('Votre demande a été acceptée - ou pas.');
    }

    submit(event) {
        // Vérifier si les champs honeypot sont remplis
        if (this.mobilePhoneTarget.value !== '' || this.workEmailTarget.value !== '') {
            event.preventDefault();
            this.blockForm();
        }
    }
} 