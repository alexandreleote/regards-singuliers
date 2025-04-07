import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['input', 'toggle']

    connect() {
        // Au chargement, on cache le numéro et on affiche le texte
        this.inputTarget.classList.add('hidden');
        this.toggleTarget.classList.remove('hidden');
    }

    toggle() {
        // On inverse la visibilité des deux éléments
        this.toggleTarget.classList.toggle('hidden');
        this.inputTarget.classList.toggle('hidden');
    }
} 