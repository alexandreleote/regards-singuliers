// Import des styles
import './styles/admin.css';

// Gestion du menu responsive
document.addEventListener('DOMContentLoaded', () => {
    // Toggle pour le menu latéral sur mobile
    const toggleSidebar = document.querySelector('.toggle-sidebar');
    const adminSidebar = document.querySelector('.admin-sidebar');
    
    if (toggleSidebar && adminSidebar) {
        toggleSidebar.addEventListener('click', () => {
            adminSidebar.classList.toggle('collapsed');
        });
    }

    // Gestion active du menu
    const currentPath = window.location.pathname;
    const navLinks = document.querySelectorAll('.admin-sidebar .nav-link');
    
    navLinks.forEach(link => {
        if (link.getAttribute('href') === currentPath) {
            link.classList.add('active');
        }
    });
});

// Fonction pour les confirmations de suppression
export function confirmDelete(message = 'Êtes-vous sûr de vouloir supprimer cet élément ?') {
    return confirm(message);
}

// Fonction pour les notifications
export function notify(message, type = 'success') {
    // Vous pouvez implémenter votre propre système de notification ici
    // Par exemple, en utilisant toastr ou une autre bibliothèque
    console.log(`${type}: ${message}`);
}
