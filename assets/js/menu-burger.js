document.addEventListener('DOMContentLoaded', function() {
    const burgerMenuBtn = document.querySelector('.burger-menu-btn');
    const navMobile = document.querySelector('.nav-mobile');
    const menuOverlay = document.querySelector('.menu-overlay');
    const body = document.body;
    
    if (burgerMenuBtn && navMobile && menuOverlay) {
        // Toggle menu function
        function toggleMenu(show) {
            if (show === undefined) {
                // Toggle mode
                navMobile.classList.toggle('open');
                menuOverlay.classList.toggle('active');
                burgerMenuBtn.classList.toggle('active');
                
                // Prevent body scrolling when menu is open
                if (navMobile.classList.contains('open')) {
                    body.style.overflow = 'hidden';
                } else {
                    body.style.overflow = '';
                }
            } else if (show) {
                // Show menu
                navMobile.classList.add('open');
                menuOverlay.classList.add('active');
                burgerMenuBtn.classList.add('active');
                body.style.overflow = 'hidden';
            } else {
                // Hide menu
                navMobile.classList.remove('open');
                menuOverlay.classList.remove('active');
                burgerMenuBtn.classList.remove('active');
                body.style.overflow = '';
            }
        }
        
        // Menu burger click
        burgerMenuBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            toggleMenu();
        });

        // Close menu when clicking on links
        const allLinks = navMobile.querySelectorAll('a');
        allLinks.forEach(link => {
            link.addEventListener('click', function() {
                toggleMenu(false);
            });
        });

        // Close menu when clicking on overlay
        menuOverlay.addEventListener('click', function() {
            toggleMenu(false);
        });
        
        // Handle window resize - close mobile menu when switching to desktop
        window.addEventListener('resize', function() {
            if (window.innerWidth > 991.98 && navMobile.classList.contains('open')) {
                toggleMenu(false);
            }
        });
    }
});