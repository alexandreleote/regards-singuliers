import { showToast } from './notifications.js';

export function initializeDiscussions() {
    const messageForm = document.getElementById('messageForm');
    const messagesContainer = document.getElementById('messagesContainer');
    const discussionId = messageForm ? messageForm.querySelector('input[name="discussionId"]')?.value : null;
    const toggleLockBtn = document.getElementById('toggleLockBtn');

    // Faire défiler jusqu'aux derniers messages
    function scrollToBottom() {
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
    }
    scrollToBottom();

    // Gérer le verrouillage/déverrouillage
    if (toggleLockBtn) {
        toggleLockBtn.addEventListener('click', function() {
            const discussionId = this.dataset.discussionId;
            
            fetch(`/discussion/toggle-lock/${discussionId}`, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Erreur réseau');
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    // Mettre à jour l'interface
                    const isLocked = data.isLocked;
                    toggleLockBtn.innerHTML = isLocked ? 
                        '<i class="fas fa-lock"></i> Déverrouiller' : 
                        '<i class="fas fa-unlock"></i> Verrouiller';
                    
                    // Mettre à jour le formulaire
                    const textarea = messageForm.querySelector('textarea');
                    const submitBtn = messageForm.querySelector('button[type="submit"]');
                    
                    if (isLocked) {
                        messageForm.setAttribute('disabled', 'disabled');
                        textarea.setAttribute('disabled', 'disabled');
                        textarea.placeholder = 'Cette discussion est verrouillée';
                        submitBtn.setAttribute('disabled', 'disabled');
                        showToast('La discussion a été verrouillée', 'info');
                    } else {
                        messageForm.removeAttribute('disabled');
                        textarea.removeAttribute('disabled');
                        textarea.placeholder = 'Écrivez votre message...';
                        submitBtn.removeAttribute('disabled');
                        showToast('La discussion a été déverrouillée', 'info');
                    }
                } else {
                    throw new Error(data.error || 'Une erreur est survenue');
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                showToast('Une erreur est survenue lors du verrouillage/déverrouillage de la discussion', 'error');
            });
        });
    }

    // Gérer l'envoi du message
    if (messageForm) {
        messageForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(messageForm);

            fetch('/discussion/send', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Erreur réseau');
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    const messageElement = document.createElement('div');
                    messageElement.className = 'message message-user';
                    messageElement.innerHTML = `
                        <div class="message-content">${data.message.content}</div>
                        <div class="message-time">${data.message.sentAt}</div>
                    `;
                    messagesContainer.appendChild(messageElement);
                    scrollToBottom();
                    messageForm.reset();
                    showToast('Message envoyé avec succès', 'success');
                } else {
                    throw new Error(data.error || 'Une erreur est survenue');
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                showToast('Une erreur est survenue lors de l\'envoi du message', 'error');
            });
        });
    }

    // Vérifier les nouveaux messages toutes les 30 secondes
    if (discussionId) {
        let checkMessagesInterval;
        
        function checkNewMessages() {
            const url = new URL('/discussion/check-new', window.location.origin);
            url.searchParams.append('discussionId', discussionId);

            return fetch(url, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => {
                if (!response.ok) {
                    if (response.status === 403) {
                        // Si l'utilisateur n'est plus authentifié, on arrête les vérifications
                        clearInterval(checkMessagesInterval);
                        throw new Error('Session expirée');
                    }
                    throw new Error('Erreur réseau');
                }
                return response.json();
            })
            .then(data => {
                if (data.newMessages) {
                    data.messages.forEach(message => {
                        const messageElement = document.createElement('div');
                        messageElement.className = 'message message-admin';
                        messageElement.innerHTML = `
                            <div class="message-content">${message.content}</div>
                            <div class="message-time">${message.sentAt}</div>
                        `;
                        messagesContainer.appendChild(messageElement);
                    });
                    scrollToBottom();
                    showToast('Nouveau(x) message(s) reçu(s)', 'info');
                }
            })
            .catch(error => {
                console.error('Erreur lors de la vérification des nouveaux messages:', error);
                if (error.message === 'Session expirée') {
                    showToast('Votre session a expiré. Veuillez vous reconnecter.', 'error');
                    setTimeout(() => window.location.reload(), 2000);
                } else {
                    showToast('Erreur lors de la vérification des nouveaux messages', 'error');
                }
            });
        }

        // Première vérification immédiate
        checkNewMessages();
        
        // Puis toutes les 30 secondes
        checkMessagesInterval = setInterval(checkNewMessages, 30000);

        // Nettoyer l'intervalle quand l'utilisateur quitte la page
        window.addEventListener('beforeunload', () => {
            clearInterval(checkMessagesInterval);
        });
    }
}