// Import de Stimulus et des contrôleurs
import './bootstrap.js';
import './controllers/menu-burger_controller.js';
import './controllers/phone-toggle_controller.js';

// Import des styles
import './styles/app.css';

// Gestionnaire d'erreurs global pour les promesses non gérées
window.addEventListener('unhandledrejection', function(event) {
    console.error('Promesse non gérée :', event.reason);
    
    // Ajout d'une notification visuelle pour l'utilisateur
    const notification = document.createElement('div');
    notification.className = 'error-notification';
    notification.textContent = 'Une erreur est survenue. Veuillez réessayer.';
    document.body.appendChild(notification);
    setTimeout(() => notification.remove(), 3000);
});