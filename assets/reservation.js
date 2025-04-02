// Code JavaScript pour la page de réservation
document.addEventListener('DOMContentLoaded', function() {
    // Chargement du script Calendly
    const script = document.createElement('script');
    script.src = 'https://assets.calendly.com/assets/external/widget.js';
    script.async = true;
    document.body.appendChild(script);

    // Initialisation du widget Calendly une fois le script chargé
    script.onload = function() {
        const container = document.getElementById('calendly-container');
        if (container) {
            const url = container.getAttribute('data-url');
            if (url) {
                Calendly.initInlineWidget({
                    url: url,
                    parentElement: container,
                    prefill: {},
                    utm: {}
                });

                // Écouteur d'événements pour la confirmation de réservation
                window.addEventListener('message', function(e) {
                    if (e.data.event === 'calendly.event_scheduled') {
                        // Récupération du token CSRF
                        const csrfToken = document.querySelector('[data-csrf-token]').dataset.csrfToken;
                        
                        // Récupération de l'ID du service
                        const serviceId = document.querySelector('.service-info').dataset.serviceId;
                        
                        // Redirection vers la page de paiement
                        window.location.href = `/reservation/payment/${serviceId}`;
                    }
                });
            }
        }
    };
}); 