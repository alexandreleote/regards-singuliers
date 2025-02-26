export default class FlashMessageManager {
    constructor(options = {}) {
        this.duration = options.duration || 10000; // 10 seconds par défaut
        this.containerSelector = options.containerSelector || '.flash-messages';
        this.messageSelector = options.messageSelector || '.alert';
        
        this.init();
    }

    init() {
        document.addEventListener('DOMContentLoaded', () => {
            this.setupFlashMessages();
        });
    }

    setupFlashMessages() {
        const container = document.querySelector(this.containerSelector);
        
        if (!container) return;

        const messages = container.querySelectorAll(this.messageSelector);
        
        messages.forEach(message => {
            this.autoHideMessage(message);
        });
    }

    autoHideMessage(message) {
        // Ajoute une transition CSS
        message.style.transition = 'opacity 0.5s ease-out';

        // Définit le timer de disparition
        const timer = setTimeout(() => {
            message.style.opacity = '0';
            
            // Supprime le message après la transition
            setTimeout(() => {
                message.remove();
            }, 500);
        }, this.duration);

        // Permet d'annuler le timer si l'utilisateur survole le message
        message.addEventListener('mouseenter', () => {
            clearTimeout(timer);
        });

        // Reprend le timer si la souris quitte le message
        message.addEventListener('mouseleave', () => {
            this.autoHideMessage(message);
        });
    }

    // Méthode statique pour une utilisation rapide
    static init(options) {
        return new FlashMessageManager(options);
    }
}
