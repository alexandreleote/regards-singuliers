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

import { initFormValidation } from './js/modules/form-validation.js';

document.addEventListener('DOMContentLoaded', () => {
    initFormValidation(); // This will validate all forms with .needs-validation class
    
    // If you want to validate a specific form with a different selector:
    // initFormValidation('#specific-form-id');
});

console.log('This log comes from assets/app.js - welcome to AssetMapper! 🎉');
