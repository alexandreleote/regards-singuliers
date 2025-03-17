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
import './js/show-number.js';
import './js/faq.js';
import './js/studio.js';
import './js/map.js';
import './js/menu-burger.js';

console.log('This log comes from assets/app.js - welcome to AssetMapper! ��');

// Burger menu
document.addEventListener('DOMContentLoaded', () => {
    const burgerButton = document.querySelector('.menu-burger');
    const navLinks = document.querySelector('.nav-links');

    if (burgerButton && navLinks) {
        burgerButton.addEventListener('click', () => {
            navLinks.classList.toggle('active');
            burgerButton.classList.toggle('active');
        });
    }
});

// FAQ Accordéon
document.addEventListener('DOMContentLoaded', () => {
    const faqItems = document.querySelectorAll('.faq-item');

    faqItems.forEach(item => {
        const question = item.querySelector('.faq-question');
        
        if (question) {
            question.addEventListener('click', () => {
                const isActive = item.classList.contains('active');
                
                // Ferme tous les autres items
                faqItems.forEach(otherItem => {
                    if (otherItem !== item) {
                        otherItem.classList.remove('active');
                    }
                });
                
                // Toggle l'item actuel
                item.classList.toggle('active');
            });
        }
    });
});

// Header scroll
document.addEventListener('DOMContentLoaded', () => {
    const header = document.querySelector('.site-header');
    const scrollThreshold = 50;

    if (header) {
        window.addEventListener('scroll', () => {
            if (window.scrollY > scrollThreshold) {
                header.classList.add('scrolled');
            } else {
                header.classList.remove('scrolled');
            }
        });
    }
});

// Phone Number Toggle
const phoneController = {
    initialize() {
        const showPhoneButton = document.getElementById('show-phone');
        const phoneNumber = document.getElementById('phone-number');

        if (showPhoneButton && phoneNumber) {
            showPhoneButton.addEventListener('click', () => {
                showPhoneButton.classList.add('hidden');
                phoneNumber.classList.remove('hidden');
            });
        }
    }
};

document.addEventListener('DOMContentLoaded', () => {
    phoneController.initialize();
});