// Dashboard-specific JavaScript
document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.querySelector('.admin-sidebar');
    const main = document.querySelector('.admin-main');
    
    // Ajouter un bouton pour le menu mobile
    if (window.innerWidth <= 768) {
        const toggleButton = document.createElement('button');
        toggleButton.className = 'btn btn-dark d-md-none';
        toggleButton.innerHTML = '<i class="fas fa-bars"></i>';
        toggleButton.style.position = 'fixed';
        toggleButton.style.top = '1rem';
        toggleButton.style.left = '1rem';
        toggleButton.style.zIndex = '1050';
        
        document.body.appendChild(toggleButton);
        
        toggleButton.addEventListener('click', function() {
            sidebar.classList.toggle('show');
            main.classList.toggle('sidebar-shown');
        });
    }
});
