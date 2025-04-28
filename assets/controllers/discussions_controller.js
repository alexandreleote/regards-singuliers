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
            
            // Vérifier les nouveaux messages toutes les 30 secondes
            if (this.discussionIdValue > 0) {
                this.lastMessageCount = this.getMessageCount();
                this.checkNewMessagesInterval = setInterval(() => this.checkNewMessages(), 30000);
                
                // Vérifier immédiatement s'il y a des nouveaux messages
                this.checkNewMessages();
            }
        }
        
        // Initialiser le bouton de verrouillage de discussion
        const toggleLockBtn = document.getElementById('toggleLockBtn');
        if (toggleLockBtn) {
            toggleLockBtn.addEventListener('click', this.handleToggleLock.bind(this));
        }
    }
    
    getMessageCount() {
        return this.hasMessageListTarget ? this.messageListTarget.querySelectorAll('.message').length : 0;
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

            const result = await response.json();
            
            if (result.success) {
                this.messageInputTarget.value = '';
                this.showNotification('Message envoyé avec succès', 'success');
                // Mettre à jour la liste des messages sans recharger la page
                this.updateMessageList(result);
            } else {
                throw new Error(result.message || 'Erreur lors de l\'envoi du message');
            }
        } catch (error) {
            console.error('Erreur lors de l\'envoi du message:', error);
            this.showNotification('Une erreur est survenue lors de l\'envoi du message. Veuillez réessayer.', 'error');
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
            
            this.messageListTarget.insertAdjacentHTML('afterbegin', messageHtml);
            this.messageListTarget.scrollTop = this.messageListTarget.scrollHeight;
        }
    }

    async checkNewMessages() {
        if (!this.discussionIdValue) return;
        
        try {
            // Ajouter un timestamp pour éviter la mise en cache
            const timestamp = new Date().getTime();
            
            // Vérifier les nouveaux messages sans les marquer comme lus
            const response = await fetch(`/discussion/check-new?discussionId=${this.discussionIdValue}&t=${timestamp}`, {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Cache-Control': 'no-cache, no-store, must-revalidate'
                },
                cache: 'no-store'
            });
            
            if (!response.ok) return;
            
            const data = await response.json();
            
            // Vérifier s'il y a de nouveaux messages
            if (data.newMessages === true && data.count > 0) {
                // Afficher une notification pour chaque nouveau message
                const messageCount = data.count > 1 ? `${data.count} nouveaux messages` : 'un nouveau message';
                this.showNotification(`Vous avez reçu ${messageCount}`, 'info');
                
                // Attendre un peu avant de marquer comme lus et recharger
                setTimeout(async () => {
                    // Marquer les messages comme lus
                    await fetch(`/discussion/check-new?discussionId=${this.discussionIdValue}&markAsRead=true&t=${new Date().getTime()}`, {
                        method: 'GET',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });
                    
                    // Recharger la page pour afficher les nouveaux messages
                    window.location.reload();
                }, 1500); // Délai pour permettre à l'utilisateur de voir la notification
            }
        } catch (error) {
            console.error('Erreur lors de la vérification des nouveaux messages:', error);
        }
    }
    
    showNotification(message, type = 'info') {
        // Utiliser le contrôleur de notifications global s'il existe
        const notificationsController = this.application.getControllerForElementAndIdentifier(
            document.querySelector('[data-controller="notifications"]'),
            'notifications'
        );
        
        if (notificationsController) {
            notificationsController.show(message, type);
        } else {
            // Fallback si le contrôleur de notifications n'est pas disponible
            const toastContainer = document.getElementById('toastContainer');
            
            if (toastContainer) {
                const toast = document.createElement('div');
                toast.className = `toast ${type}`;
                
                const iconClass = type === 'success' ? 'fa-check-circle' : 
                                 type === 'error' ? 'fa-exclamation-circle' : 'fa-info-circle';
                
                toast.innerHTML = `
                    <span class="toast-icon"><i class="fas ${iconClass}"></i></span>
                    <span class="toast-message">${message}</span>
                `;
                
                toastContainer.appendChild(toast);
                
                // Afficher le toast avec animation
                setTimeout(() => toast.classList.add('show'), 10);
                
                // Supprimer le toast après un délai
                setTimeout(() => {
                    toast.classList.remove('show');
                    setTimeout(() => toast.remove(), 300);
                }, 5000);
            }
        }
    }
    
    async handleToggleLock(event) {
        event.preventDefault();
        
        const button = event.currentTarget;
        const discussionId = button.getAttribute('data-discussion-id');
        const isLocked = button.getAttribute('data-is-locked') === 'true';
        
        if (!discussionId) {
            console.error('ID de discussion non trouvé');
            return;
        }
        
        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
            if (!csrfToken) {
                throw new Error('CSRF token non trouvé');
            }
            
            const response = await fetch(`/discussion/toggle-lock/${discussionId}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            
            if (!response.ok) {
                throw new Error(`Erreur HTTP: ${response.status}`);
            }
            
            const result = await response.json();
            
            if (result.success) {
                // Mettre à jour l'état du bouton
                const newIsLocked = result.isLocked;
                button.setAttribute('data-is-locked', newIsLocked ? 'true' : 'false');
                
                // Mettre à jour l'icône et le texte
                button.innerHTML = newIsLocked ? 
                    '<i class="fas fa-lock" aria-hidden="true"></i> Déverrouiller' : 
                    '<i class="fas fa-unlock" aria-hidden="true"></i> Verrouiller';
                
                // Mettre à jour l'attribut aria-label
                button.setAttribute('aria-label', 
                    newIsLocked ? 'Déverrouiller la discussion' : 'Verrouiller la discussion'
                );
                
                // Mettre à jour l'état du formulaire
                if (this.hasFormTarget) {
                    if (newIsLocked) {
                        this.formTarget.setAttribute('disabled', 'disabled');
                        if (this.hasMessageInputTarget) {
                            this.messageInputTarget.setAttribute('placeholder', 'Cette discussion est verrouillée');
                            this.messageInputTarget.setAttribute('disabled', 'disabled');
                        }
                    } else {
                        this.formTarget.removeAttribute('disabled');
                        if (this.hasMessageInputTarget) {
                            this.messageInputTarget.setAttribute('placeholder', 'Écrivez votre message...');
                            this.messageInputTarget.removeAttribute('disabled');
                        }
                    }
                }
                
                // Afficher une notification
                this.showNotification(
                    newIsLocked ? 'Discussion verrouillée avec succès' : 'Discussion déverrouillée avec succès', 
                    'success'
                );
            } else {
                throw new Error(result.error || 'Erreur lors du verrouillage/déverrouillage de la discussion');
            }
        } catch (error) {
            console.error('Erreur lors du verrouillage/déverrouillage:', error);
            this.showNotification('Une erreur est survenue. Veuillez réessayer.', 'error');
        }
    }

    disconnect() {
        if (this.hasFormTarget) {
            this.formTarget.removeEventListener('submit', this.handleSubmit.bind(this));
        }
        
        const toggleLockBtn = document.getElementById('toggleLockBtn');
        if (toggleLockBtn) {
            toggleLockBtn.removeEventListener('click', this.handleToggleLock.bind(this));
        }
        
        if (this.checkNewMessagesInterval) {
            clearInterval(this.checkNewMessagesInterval);
        }
    }
} 