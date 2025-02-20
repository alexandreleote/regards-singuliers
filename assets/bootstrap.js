// assets/bootstrap.js
// Import des styles
import './styles/app.css';

// Import de bootstrap
import * as bootstrap from 'bootstrap';

// Initialisation des composants Bootstrap qui nécessitent du JavaScript
document.addEventListener('DOMContentLoaded', () => {
    // Activer tous les tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Activer tous les popovers
    const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });
});
