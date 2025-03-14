import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['show', 'number']
    static classes = ['hidden']

    connect() {
        // Le code ne s'exécute que si le contrôleur est connecté (présent sur la page)
        this.numberTarget.classList.add(this.hiddenClass);
    }

    toggle() {
        this.numberTarget.classList.toggle(this.hiddenClass);
    }
} 