document.addEventListener('DOMContentLoaded', function() {
    const menuBurger = document.querySelector('.menu-burger');
    const navLinks = document.querySelector('.nav-links');
    const navAuth = document.querySelector('.nav-auth');
    const body = document.body;
    
    if (menuBurger && navLinks && navAuth) {
        // Toggle menu function to avoid code duplication
        function toggleMenu(show) {
            const icon = menuBurger.querySelector('i');
            
            if (show === undefined) {
                // Toggle mode
                navLinks.classList.toggle('active');
                navAuth.classList.toggle('active');
                icon.classList.toggle('fa-bars');
                icon.classList.toggle('fa-xmark');
                
                // Prevent body scrolling when menu is open
                if (navLinks.classList.contains('active')) {
                    body.style.overflow = 'hidden';
                } else {
                    body.style.overflow = '';
                }
            } else if (show) {
                // Show menu
                navLinks.classList.add('active');
                navAuth.classList.add('active');
                icon.classList.remove('fa-bars');
                icon.classList.add('fa-xmark');
                body.style.overflow = 'hidden';
            } else {
                // Hide menu
                navLinks.classList.remove('active');
                navAuth.classList.remove('active');
                icon.classList.add('fa-bars');
                icon.classList.remove('fa-xmark');
                body.style.overflow = '';
            }
        }
        
        // Menu burger click
        menuBurger.addEventListener('click', function(e) {
            e.stopPropagation();
            toggleMenu();
        });

        // Close menu when clicking on links
        const allLinks = [...navLinks.querySelectorAll('a'), ...navAuth.querySelectorAll('a')];
        allLinks.forEach(link => {
            link.addEventListener('click', function() {
                toggleMenu(false);
            });
        });

        // Close menu when clicking outside
        document.addEventListener('click', function(event) {
            if (navLinks.classList.contains('active') && 
                !event.target.closest('.nav-links') && 
                !event.target.closest('.nav-auth') && 
                !event.target.closest('.menu-burger')) {
                toggleMenu(false);
            }
        });
        
        // Handle window resize - close mobile menu when switching to desktop
        window.addEventListener('resize', function() {
            if (window.innerWidth > 768 && navLinks.classList.contains('active')) {
                toggleMenu(false);
            }
        });
    }
});