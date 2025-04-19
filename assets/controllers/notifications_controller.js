import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['container']
    static values = {
        timeout: { type: Number, default: 5000 },
        checkInterval: { type: Number, default: 30000 }, // Vérifier les nouveaux messages toutes les 30 secondes
        checkMessages: { type: Boolean, default: false } // Activer la vérification des messages
    }

    connect() {
        this.notifications = [];
        console.log('Notifications controller connected');
        
        // Si la vérification des messages est activée
        if (this.checkMessagesValue) {
            console.log('Vérification des messages activée');
            // Vérifier les nouveaux messages après un court délai puis à intervalle régulier
            setTimeout(() => {
                this.checkNewMessages();
                this.interval = setInterval(() => this.checkNewMessages(), this.checkIntervalValue);
            }, 1000);
        }
    }

    /**
     * Vérifie s'il y a de nouveaux messages
     */
    async checkNewMessages() {
        try {
            console.log('Vérification des nouveaux messages...');
            
            // Ajouter un timestamp pour éviter la mise en cache
            const timestamp = new Date().getTime();
            const response = await fetch(`/discussion/check-new?t=${timestamp}&countOnly=true&force=true`, {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Cache-Control': 'no-cache, no-store, must-revalidate',
                    'Pragma': 'no-cache'
                },
                cache: 'no-store'
            });
            
            if (!response.ok) {
                console.error('Erreur de réponse:', response.status);
                return;
            }
            
            const data = await response.json();
            console.log('Résultat de la vérification:', data);
            
            // Stocker le dernier nombre de messages non lus
            const previousUnreadCount = this.lastUnreadCount || 0;
            this.lastUnreadCount = data.unreadCount || 0;
            
            // S'il y a des nouveaux messages et que le nombre a augmenté
            if (data.newMessages && data.unreadCount > 0 && data.unreadCount > previousUnreadCount) {
                console.log(`${data.unreadCount} nouveaux messages détectés (précédemment: ${previousUnreadCount})`);
                
                // Créer un message adapté au nombre de nouveaux messages
                const newMessageCount = data.unreadCount - previousUnreadCount;
                const message = newMessageCount > 1 
                    ? `Vous avez reçu ${newMessageCount} nouveaux messages` 
                    : 'Vous avez reçu un nouveau message';
                
                // Afficher la notification
                this.show(message, 'info');
                
                // Jouer un son de notification si possible
                this.playNotificationSound();
            }
        } catch (error) {
            console.error('Erreur lors de la vérification des nouveaux messages:', error);
        }
    }
    
    /**
     * Joue un son de notification
     */
    playNotificationSound() {
        try {
            // Créer un élément audio et jouer un son de notification
            const audio = new Audio('/build/sounds/notification.mp3');
            audio.volume = 0.5;
            
            // Vérifier si l'utilisateur a interagi avec la page
            const hasInteracted = document.documentElement.hasAttribute('data-user-interacted');
            
            if (hasInteracted) {
                audio.play().catch(e => {
                    console.log('Impossible de jouer le son de notification:', e);
                    // Stocker l'intention de jouer le son pour plus tard
                    this.pendingSound = true;
                });
            } else {
                // Stocker l'intention de jouer le son pour plus tard
                this.pendingSound = true;
                
                // Ajouter un écouteur d'événement unique pour jouer le son après interaction
                if (!this.hasSetupInteractionListener) {
                    this.hasSetupInteractionListener = true;
                    const playPendingSound = () => {
                        if (this.pendingSound) {
                            audio.play().catch(e => console.log('Impossible de jouer le son de notification:', e));
                            this.pendingSound = false;
                        }
                    };
                    
                    // Liste des événements d'interaction utilisateur
                    const interactionEvents = ['click', 'touchstart', 'keydown'];
                    
                    interactionEvents.forEach(eventType => {
                        document.addEventListener(eventType, () => {
                            document.documentElement.setAttribute('data-user-interacted', 'true');
                            playPendingSound();
                        }, { once: true });
                    });
                }
            }
        } catch (error) {
            console.log('Son de notification non disponible:', error);
        }
    }

    /**
     * Affiche une notification
     */
    show(message, type = 'info') {
        console.log('Affichage d\'une notification:', message, type);
        
        // Créer le toast avec le style approprié
        const toast = document.createElement('div');
        toast.className = `toast ${type}`;
        
        // Déterminer l'icône appropriée selon le type
        const iconClass = type === 'success' ? 'fa-check-circle' : 
                         type === 'error' ? 'fa-exclamation-circle' : 'fa-info-circle';
        
        // Ajouter le contenu HTML du toast
        toast.innerHTML = `
            <span class="toast-icon"><i class="fas ${iconClass}"></i></span>
            <span class="toast-message">${message}</span>
        `;
        
        // Ajouter le toast au conteneur
        this.containerTarget.appendChild(toast);
        this.notifications.push(toast);
        
        // Ajouter la classe show après un court délai pour déclencher l'animation
        setTimeout(() => toast.classList.add('show'), 10);
        
        // Masquer le toast après un délai
        setTimeout(() => {
            this.hide(toast);
        }, this.timeoutValue);
    }

    /**
     * Masque une notification
     */
    hide(toast) {
        // Retirer la classe show pour déclencher l'animation de sortie
        toast.classList.remove('show');
        
        // Supprimer le toast après la fin de l'animation
        setTimeout(() => {
            toast.remove();
            this.notifications = this.notifications.filter(n => n !== toast);
        }, 400);
    }

    disconnect() {
        // Nettoyer les notifications
        this.notifications.forEach(notification => notification.remove());
        this.notifications = [];
        
        // Nettoyer l'intervalle de vérification des messages
        if (this.interval) {
            clearInterval(this.interval);
        }
    }
} 