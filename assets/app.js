import './bootstrap.js';
/*
 * Welcome to your app's main JavaScript file!
 *
 * This file will be included onto the page via the importmap() Twig function,
 * which should already be in your base.html.twig.
 */

/* CSS Imports */
import './styles/app.css';

/* JavaScript Imports */
import './js/header-scroll.js';
import './js/faq.js';
import './js/studio.js';
import './js/map.js';
import './js/menu-burger.js';
import './js/phoneNumberToggle.js';
import './js/notifications.js';
import { initializeDiscussions } from './js/discussions.js';

// Initialiser les discussions si on est sur la page des discussions
document.addEventListener('DOMContentLoaded', function() {
    if (document.getElementById('messageForm')) {
        initializeDiscussions();
    }
});

console.log('This log comes from assets/app.js - welcome to AssetMapper! 🎉');