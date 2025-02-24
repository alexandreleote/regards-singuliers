// Gestion du menu responsive
document.addEventListener('DOMContentLoaded', () => {
    // Toggle pour le menu latéral sur mobile
    const toggleSidebar = document.querySelector('.toggle-sidebar');
    const adminSidebar = document.querySelector('.admin-sidebar');
    
    if (toggleSidebar && adminSidebar) {
        toggleSidebar.addEventListener('click', () => {
            adminSidebar.classList.toggle('show');
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
function confirmDelete(message = 'Êtes-vous sûr de vouloir supprimer cet élément ?') {
    return confirm(message);
}

// Fonction pour les notifications
function notify(message, type = 'success') {
    // Implémentation des notifications
    console.log(`${type}: ${message}`);
}

export { confirmDelete, notify };
