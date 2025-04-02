import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['container']
    static values = {
        timeout: { type: Number, default: 5000 }
    }

    connect() {
        this.notifications = [];
    }

    show(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.textContent = message;
        
        this.containerTarget.appendChild(notification);
        this.notifications.push(notification);

        setTimeout(() => {
            this.hide(notification);
        }, this.timeoutValue);
    }

    hide(notification) {
        notification.classList.add('fade-out');
        setTimeout(() => {
            notification.remove();
            this.notifications = this.notifications.filter(n => n !== notification);
        }, 300);
    }

    disconnect() {
        this.notifications.forEach(notification => notification.remove());
        this.notifications = [];
    }
} 