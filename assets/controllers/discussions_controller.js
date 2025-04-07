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
        }
    }

    initializeDiscussions() {
        if (this.isUserConnectedValue) {
            this.setupMessageForm();
            this.loadMessages();
        }
    }

    setupMessageForm() {
        this.formTarget.addEventListener('submit', this.handleSubmit.bind(this));
    }

    async handleSubmit(event) {
        event.preventDefault();
        
        const formData = new FormData(this.formTarget);
        const message = formData.get('message');

        if (!message.trim()) return;

        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
            if (!csrfToken) {
                throw new Error('CSRF token non trouvé');
            }

            const response = await fetch(`/discussion/${this.discussionIdValue}/message`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': csrfToken
                },
                body: JSON.stringify({ message })
            });

            if (!response.ok) {
                throw new Error(`Erreur HTTP: ${response.status}`);
            }

            this.messageInputTarget.value = '';
            await this.loadMessages();
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

    async loadMessages() {
        try {
            const response = await fetch(`/discussion/${this.discussionIdValue}/messages`);
            if (!response.ok) {
                throw new Error(`Erreur HTTP: ${response.status}`);
            }
            const messages = await response.json();
            this.updateMessageList(messages);
        } catch (error) {
            console.error('Erreur lors du chargement des messages:', error);
            // Ajout d'une notification visuelle pour l'utilisateur
            const notification = document.createElement('div');
            notification.className = 'error-notification';
            notification.textContent = 'Une erreur est survenue lors du chargement des messages.';
            document.body.appendChild(notification);
            setTimeout(() => notification.remove(), 3000);
        }
    }

    updateMessageList(messages) {
        this.messageListTarget.innerHTML = messages.map(message => `
            <div class="message ${message.isUser ? 'user-message' : 'admin-message'}">
                <div class="message-content">${message.content}</div>
                <div class="message-time">${message.createdAt}</div>
            </div>
        `).join('');
    }

    disconnect() {
        if (this.hasFormTarget) {
            this.formTarget.removeEventListener('submit', this.handleSubmit.bind(this));
        }
    }
} 