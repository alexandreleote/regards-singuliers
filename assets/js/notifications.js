let lastNotificationTime = 0;
const NOTIFICATION_COOLDOWN = 5000; // 5 secondes entre chaque notification
let checkInterval = null;
let isChecking = false;
let isInitialized = false;
let currentRequest = null;
let lastDiscussionId = null;

export function showToast(message, type = 'info') {
    const now = Date.now();
    if (now - lastNotificationTime < NOTIFICATION_COOLDOWN) {
        return;
    }
    lastNotificationTime = now;

    let toastContainer = document.getElementById('toastContainer');
    
    if (!toastContainer) {
        toastContainer = document.createElement('div');
        toastContainer.id = 'toastContainer';
        toastContainer.className = 'toast-container';
        document.body.appendChild(toastContainer);
    }

    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    
    let icon = '';
    switch(type) {
        case 'success':
            icon = 'fa-check-circle';
            break;
        case 'error':
            icon = 'fa-exclamation-circle';
            break;
        default:
            icon = 'fa-info-circle';
    }

    toast.innerHTML = `
        <i class="fas ${icon} toast-icon"></i>
        <div class="toast-content">
            <p class="toast-message">${message}</p>
        </div>
        <button class="toast-close" onclick="this.parentElement.remove()">
            <i class="fas fa-times"></i>
        </button>
    `;

    toastContainer.appendChild(toast);
    
    setTimeout(() => toast.classList.add('show'), 10);

    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => toast.remove(), 300);
    }, 10000);
}

async function checkUnreadMessages() {
    if (isChecking || !isInitialized) return;
    isChecking = true;

    try {
        // Annuler la requête précédente si elle existe
        if (currentRequest) {
            currentRequest.abort();
        }

        const controller = new AbortController();
        currentRequest = controller;

        // Construire l'URL avec l'ID de la dernière discussion
        const url = new URL('/discussion/check-new', window.location.origin);
        if (lastDiscussionId) {
            url.searchParams.append('discussionId', lastDiscussionId);
        }

        const response = await fetch(url, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            signal: controller.signal
        });

        if (!response.ok) {
            if (response.status === 403) {
                throw new Error('Session expirée');
            }
            throw new Error('Erreur réseau');
        }

        const data = await response.json();
        if (data.newMessages) {
            showToast('Vous avez des nouveaux messages non lus', 'info');
        }
        return data;
    } catch (error) {
        if (error.name === 'AbortError') {
            console.log('Requête annulée');
            return;
        }
        console.error('Erreur lors de la vérification des nouveaux messages:', error);
        if (error.message === 'Session expirée') {
            showToast('Votre session a expiré. Veuillez vous reconnecter.', 'error');
            stopMessageChecking();
        }
        throw error;
    } finally {
        isChecking = false;
        currentRequest = null;
    }
}

export function startMessageChecking(discussionId = null) {
    if (!isInitialized) {
        isInitialized = true;
    }

    // Mettre à jour l'ID de la dernière discussion
    if (discussionId) {
        lastDiscussionId = discussionId;
    }

    if (checkInterval) {
        clearInterval(checkInterval);
    }
    
    // Première vérification immédiate
    checkUnreadMessages().catch(() => {});
    
    // Puis toutes les 30 secondes
    checkInterval = setInterval(() => {
        checkUnreadMessages().catch(() => {});
    }, 30000);
}

export function stopMessageChecking() {
    if (checkInterval) {
        clearInterval(checkInterval);
        checkInterval = null;
    }
    if (currentRequest) {
        currentRequest.abort();
        currentRequest = null;
    }
    isChecking = false;
    isInitialized = false;
    lastDiscussionId = null;
}

// Fonction pour réinitialiser complètement le système
export function resetNotificationSystem() {
    stopMessageChecking();
    const toastContainer = document.getElementById('toastContainer');
    if (toastContainer) {
        toastContainer.remove();
    }
    lastNotificationTime = 0;
} 