document.addEventListener('DOMContentLoaded', function() {
    // Vérifier si nous sommes sur une page d'authentification
    const isAuthPage = document.querySelector('.auth-container') !== null;
    
    // N'exécuter le code de header scroll que si nous ne sommes PAS sur une page d'authentification
    if (!isAuthPage) {
        const header = document.querySelector('.main-header');
        
        if (header) {
            // Fonction pour mettre à jour l'apparence du header au défilement
            function updateHeaderOnScroll() {
                if (window.scrollY > 50) {
                    header.classList.add('scrolled');
                } else {
                    header.classList.remove('scrolled');
                }
            }
            
            // Appliquer au chargement initial
            updateHeaderOnScroll();
            
            // Ajouter l'écouteur d'événement pour le défilement
            window.addEventListener('scroll', updateHeaderOnScroll);
        }
    }
});