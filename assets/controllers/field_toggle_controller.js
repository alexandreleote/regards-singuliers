import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['input', 'toggle']
    static values = {
        type: String
    }

    connect() {
        if (this.hasTypeValue && this.typeValue === 'password') {
            this.updateToggleIcon();
        }
    }

    toggle() {
        if (this.hasTypeValue && this.typeValue === 'password') {
            this.togglePassword();
        } else {
            this.toggleText();
        }
    }

    togglePassword() {
        const type = this.inputTarget.type === 'password' ? 'text' : 'password';
        this.inputTarget.type = type;
        this.updateToggleIcon();
    }

    toggleText() {
        this.toggleTarget.classList.add('hidden');
        this.inputTarget.classList.remove('hidden');
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