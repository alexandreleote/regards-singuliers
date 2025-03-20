document.addEventListener('DOMContentLoaded', function() {
    // Vérifier si nous sommes sur une page d'authentification
    const isAuthPage = document.querySelector('.auth-container') !== null;
    
    // N'exécuter le code de header scroll que si nous ne sommes PAS sur une page d'authentification
    if (!isAuthPage) {
        const header = document.querySelector('.main-header');
        const userMenuBtn = document.querySelector('.user-menu-btn');
        const userNavMobile = document.querySelector('.user-nav-mobile');
        const menuOverlay = document.querySelector('.menu-overlay');
        
        // Fonction pour mettre à jour l'apparence du header au défilement
        function updateHeaderOnScroll() {
            if (window.scrollY > 50) {
                header.classList.add('scrolled');
            } else {
                header.classList.remove('scrolled');
            }
        }
        
        // Gestion du menu utilisateur mobile
        if (userMenuBtn && userNavMobile) {
            userMenuBtn.addEventListener('click', function() {
                userNavMobile.classList.toggle('open');
                menuOverlay.classList.toggle('active');
            });

            // Fermer le menu utilisateur au clic sur l'overlay
            menuOverlay.addEventListener('click', function() {
                userNavMobile.classList.remove('open');
                menuOverlay.classList.remove('active');
            });

            // Fermer le menu utilisateur au clic sur un lien
            const userNavLinks = userNavMobile.querySelectorAll('.user-nav-link-mobile');
            userNavLinks.forEach(link => {
                link.addEventListener('click', function() {
                    userNavMobile.classList.remove('open');
                    menuOverlay.classList.remove('active');
                });
            });
        }
        
        // Appliquer au chargement initial
        updateHeaderOnScroll();
        
        // Ajouter l'écouteur d'événement pour le défilement
        window.addEventListener('scroll', updateHeaderOnScroll);
    }
});