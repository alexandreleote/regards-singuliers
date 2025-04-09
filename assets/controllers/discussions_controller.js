import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['form', 'messageList', 'messageInput']
    static values = {
        discussionId: Number,
        isUserConnected: Boolean
    }

    connect() {
        if (this.hasFormTarget) {
            this.initializeDiscussions();
            this.scrollToBottom();
        }
    }

    scrollToBottom() {
        if (this.hasMessageListTarget) {
            this.messageListTarget.scrollTop = this.messageListTarget.scrollHeight;
        }
    }

    initializeDiscussions() {
        if (this.isUserConnectedValue) {
            this.setupMessageForm();
        }
    }

    setupMessageForm() {
        this.formTarget.addEventListener('submit', this.handleSubmit.bind(this));
    }

    async handleSubmit(event) {
        event.preventDefault();
        
        const formData = new FormData(this.formTarget);
        const content = formData.get('content');

        if (!content || !content.trim()) return;

        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
            if (!csrfToken) {
                throw new Error('CSRF token non trouvé');
            }

            const response = await fetch('/discussion/send', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-CSRF-Token': csrfToken
                },
                body: new URLSearchParams(formData)
            });

            if (!response.ok) {
                throw new Error(`Erreur HTTP: ${response.status}`);
            }

            this.messageInputTarget.value = '';
            // Recharger la page pour afficher le nouveau message
            window.location.reload();
        } catch (error) {
            console.error('Erreur lors de l\'envoi du message:', error);
            // Ajout d'une notification visuelle pour l'utilisateur
            const notification = document.createElement('div');
            notification.className = 'error-notification';
            notification.textContent = 'Une erreur est survenue lors de l\'envoi du message. Veuillez réessayer.';
            document.body.appendChild(notification);
            setTimeout(() => notification.remove(), 3000);
        }
    }

    updateMessageList(response) {
        if (response.success && response.message) {
            const message = response.message;
            const isCurrentUser = true; // Le message vient d'être envoyé par l'utilisateur actuel
            
            const messageHtml = `
                <div class="message ${isCurrentUser ? 'message-user' : 'message-admin'}">
                    <div class="message-content">${message.content}</div>
                    <div class="message-time">${message.sentAt}</div>
                </div>
            `;
            
            this.messageListTarget.insertAdjacentHTML('beforeend', messageHtml);
            this.messageListTarget.scrollTop = this.messageListTarget.scrollHeight;
        }
    }

    disconnect() {
        if (this.hasFormTarget) {
            this.formTarget.removeEventListener('submit', this.handleSubmit.bind(this));
        }
    }
} 