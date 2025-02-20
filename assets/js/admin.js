document.addEventListener('DOMContentLoaded', function() {
    const sidebarToggle = document.querySelector('.sidebar-toggle');
    const sidebar = document.querySelector('.sidebar');

    if (sidebarToggle && sidebar) {
        sidebarToggle.addEventListener('click', function(e) {
            e.preventDefault();
            sidebar.classList.toggle('minimized');
            
            // Save state in localStorage
            const isMinimized = sidebar.classList.contains('minimized');
            localStorage.setItem('sidebarMinimized', isMinimized);
        });

        // Restore state from localStorage
        const isMinimized = localStorage.getItem('sidebarMinimized') === 'true';
        if (isMinimized) {
            sidebar.classList.add('minimized');
        }
    }
});
