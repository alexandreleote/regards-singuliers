import './bootstrap.js';
import './styles/app.css';

// Import FlashMessageManager
import FlashMessageManager from './js/modules/flashMessageManager.js';

// Initialize FlashMessageManager when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    FlashMessageManager.init({
        duration: 10000, // 10 secondes
        containerSelector: '.flash-messages',
        messageSelector: '.alert'
    });
});

console.log('This log comes from assets/app.js - welcome to AssetMapper! 🎉');
