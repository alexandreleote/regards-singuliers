import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['input', 'toggle']
    static values = {
        type: String
    }

    connect() {
        this.updateToggleIcon();
    }

    toggle() {
        const type = this.inputTarget.type === 'password' ? 'text' : 'password';
        this.inputTarget.type = type;
        this.updateToggleIcon();
    }

    updateToggleIcon() {
        const icon = this.toggleTarget;
        if (this.inputTarget.type === 'password') {
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        } else {
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        }
    }
} 