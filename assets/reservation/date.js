// Import des styles
import '../styles/app.css';

// Code spécifique à la page de réservation
document.addEventListener('DOMContentLoaded', function() {
    // Récupération des éléments du DOM
    const serviceInfo = document.querySelector('.service-info');
    const serviceId = serviceInfo ? serviceInfo.dataset.serviceId : null;
    const csrfToken = document.querySelector('[data-csrf-token]').dataset.csrfToken;
    const calendlyContainer = document.getElementById('calendly-container');

    // Fonction pour nettoyer les widgets Calendly existants
    function cleanupCalendly() {
        // Supprimer tous les iframes Calendly existants
        const iframes = document.querySelectorAll('iframe[src*="calendly.com"]');
        iframes.forEach(iframe => iframe.remove());

        // Supprimer les divs root de Calendly qui pourraient être en double
        const rootDivs = document.querySelectorAll('#root');
        rootDivs.forEach((div, index) => {
            if (index > 0) div.remove();
        });

        // Réinitialiser le conteneur
        if (calendlyContainer) {
            calendlyContainer.innerHTML = '';
        }
    }

    // Fonction pour charger le script Calendly
    function loadCalendlyScript() {
        return new Promise((resolve, reject) => {
            // Nettoyer d'abord
            cleanupCalendly();

            // Si Calendly existe déjà, résoudre immédiatement
            if (window.Calendly) {
                resolve();
                return;
            }

            const script = document.createElement('script');
            script.src = 'https://assets.calendly.com/assets/external/widget.js';
            script.async = true;
            script.onload = resolve;
            script.onerror = reject;
            document.head.appendChild(script);
        });
    }

    // Fonction pour gérer la sélection de date
    async function handleDateSelection(event) {
        if (event.data.event === 'calendly.event_scheduled') {
            const eventData = event.data.payload;
            
            try {
                // Vérifier que nous avons toutes les données nécessaires
                if (!eventData || !eventData.event || !eventData.event.uri || !eventData.invitee || !eventData.invitee.uri) {
                    throw new Error('Données de l\'événement Calendly incomplètes');
                }

                // Créer la réservation via l'API
                const response = await fetch('/reservation/process-date', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify({
                        serviceId: serviceId,
                        event: {
                            event_id: eventData.event.uri,
                            invitee_id: eventData.invitee.uri
                        }
                    })
                });

                const data = await response.json();

                if (data.success) {
                    // Redirection vers la page de paiement
                    window.location.href = data.redirect;
                } else {
                    console.error('Erreur lors de la création de la réservation:', data.error);
                    alert('Une erreur est survenue lors de la création de la réservation. Veuillez réessayer.');
                }
            } catch (error) {
                console.error('Erreur lors de la création de la réservation:', error);
                alert('Une erreur est survenue lors de la création de la réservation. Veuillez réessayer.');
            }
        }
    }

    // Écouteur d'événement pour le widget Calendly
    window.addEventListener('message', handleDateSelection);

    // Charger et initialiser Calendly seulement si le conteneur existe
    if (calendlyContainer) {
        loadCalendlyScript()
            .then(() => {
                // Attendre un court instant pour s'assurer que Calendly est bien chargé
                setTimeout(() => {
                    if (typeof Calendly !== 'undefined') {
                        // Créer un nouveau widget
                        const widget = document.createElement('div');
                        widget.id = 'calendly-inline-widget';
                        widget.className = 'calendly-inline-widget';
                        widget.dataset.url = calendlyContainer.dataset.url;
                        widget.style.minWidth = '320px';
                        widget.style.height = '700px';
                        
                        calendlyContainer.appendChild(widget);

                        // Initialiser le widget
                        Calendly.initInlineWidget({
                            url: widget.dataset.url,
                            parentElement: widget,
                            prefill: {},
                            utm: {},
                            hideEventTypeDetails: true,
                            hideGdprBanner: true
                        });
                    }
                }, 100);
            })
            .catch(error => {
                console.error('Erreur lors du chargement de Calendly:', error);
            });
    }
}); 