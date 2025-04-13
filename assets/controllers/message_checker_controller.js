import { Controller } from '@hotwired/stimulus';

/**
 * Contrôleur pour vérifier les nouveaux messages sur toutes les pages
 */
export default class extends Controller {
    static values = {
        checkInterval: { type: Number, default: 10000 } // Vérifier toutes les 10 secondes pour les tests
    }

    connect() {
        console.log('Message checker connected');
        
        // Vérifier les nouveaux messages immédiatement puis à intervalle régulier
        setTimeout(() => {
            this.checkNewMessages();
            this.interval = setInterval(() => this.checkNewMessages(), this.checkIntervalValue);
        }, 1000); // Petit délai pour s'assurer que tout est chargé
    }

    disconnect() {
        // Nettoyer l'intervalle lors de la déconnexion
        if (this.interval) {
            clearInterval(this.interval);
        }
    }

    async checkNewMessages() {
        try {
            console.log('Vérification des nouveaux messages...');
            
            // Ajouter un timestamp pour éviter la mise en cache
            const timestamp = new Date().getTime();
            const response = await fetch(`/discussion/check-new?t=${timestamp}&countOnly=true`, {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Cache-Control': 'no-cache, no-store, must-revalidate'
                },
                cache: 'no-store'
            });
            
            if (!response.ok) {
                console.error('Erreur de réponse:', response.status);
                return;
            }
            
            const data = await response.json();
            console.log('Résultat de la vérification:', data);
            
            // S'il y a des nouveaux messages
            if (data.newMessages && data.unreadCount > 0) {
                console.log(`${data.unreadCount} nouveaux messages détectés`);
                this.showNotification(data.unreadCount);
            }
        } catch (error) {
            console.error('Erreur lors de la vérification des nouveaux messages:', error);
        }
    }

    showNotification(count) {
        console.log('Tentative d\'affichage de notification pour', count, 'messages');
        
        // Rechercher le conteneur de notifications
        const notificationsElement = document.querySelector('[data-controller="notifications"]');
        console.log('Élément de notifications trouvé:', notificationsElement);
        
        if (!notificationsElement) {
            // Si l'élément n'existe pas, créer une notification directement
            this.createToastDirectly(count);
            return;
        }
        
        // Utiliser le contrôleur de notifications s'il est disponible
        const notificationsController = this.application.getControllerForElementAndIdentifier(
            notificationsElement,
            'notifications'
        );
        
        if (notificationsController) {
            console.log('Contrôleur de notifications trouvé, affichage de la notification');
            const message = count > 1 
                ? `Vous avez ${count} nouveaux messages` 
                : 'Vous avez un nouveau message';
            
            notificationsController.show(message, 'info');
        } else {
            console.error('Contrôleur de notifications non trouvé');
            this.createToastDirectly(count);
        }
    }
    
    // Méthode de secours pour créer un toast directement si le contrôleur n'est pas disponible
    createToastDirectly(count) {
        console.log('Création directe d\'un toast');
        
        // Créer ou obtenir le conteneur de toasts
        let toastContainer = document.querySelector('.toast-container');
        if (!toastContainer) {
            const notificationsContainer = document.createElement('div');
            notificationsContainer.className = 'notifications-container';
            toastContainer = document.createElement('div');
            toastContainer.className = 'toast-container';
            notificationsContainer.appendChild(toastContainer);
            document.body.appendChild(notificationsContainer);
        }
        
        // Créer le toast
        const toast = document.createElement('div');
        toast.className = 'toast info';
        
        // Contenu du toast
        const message = count > 1 
            ? `Vous avez ${count} nouveaux messages` 
            : 'Vous avez un nouveau message';
        
        toast.innerHTML = `
            <span class="toast-icon"><i class="fas fa-info-circle"></i></span>
            <span class="toast-message">${message}</span>
        `;
        
        // Ajouter le toast au conteneur
        toastContainer.appendChild(toast);
        
        // Afficher le toast
        setTimeout(() => toast.classList.add('show'), 10);
        
        // Masquer le toast après un délai
        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => toast.remove(), 400);
        }, 5000);
    }
}
