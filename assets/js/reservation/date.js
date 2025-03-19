import '../../styles/reservation/date.css';

document.addEventListener('DOMContentLoaded', function() {
    window.addEventListener('message', function(e) {
        if (e.data.event && e.data.event.indexOf('calendly') === 0) {
            if (e.data.event === 'calendly.event_scheduled') {
                console.log('Événement Calendly reçu:', e.data);
                
                // Vérifier que nous avons toutes les données nécessaires
                if (!e.data.payload || !e.data.payload.event || !e.data.payload.invitee) {
                    console.error('Données Calendly incomplètes:', e.data);
                    alert('Erreur: Données de rendez-vous incomplètes');
                    return;
                }

                // Récupérer les données de l'événement
                const eventUri = e.data.payload.event.uri;
                const inviteeUri = e.data.payload.invitee.uri;
                
                // Extraire les IDs des URIs
                const eventId = eventUri.split('/').pop();
                const inviteeId = inviteeUri.split('/').pop();
                
                console.log('IDs extraits:', { eventId, inviteeId });

                // Préparer les données à envoyer
                const requestData = {
                    serviceId: document.querySelector('[data-service-id]').dataset.serviceId,
                    event: {
                        event_id: eventId,
                        invitee_id: inviteeId
                    }
                };

                console.log('Données envoyées au serveur:', requestData);

                // Envoyer les données au serveur
                fetch('/reservation/process-date', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('[data-csrf-token]').dataset.csrfToken
                    },
                    body: JSON.stringify(requestData)
                })
                .then(response => {
                    if (!response.ok) {
                        return response.json().then(data => {
                            console.error('Réponse d\'erreur du serveur:', data);
                            throw new Error(data.error || 'Erreur serveur');
                        });
                    }
                    return response.json();
                })
                .then(data => {
                    console.log('Réponse du serveur:', data);
                    if (data.success) {
                        window.location.href = data.redirect;
                    } else {
                        alert(data.error || 'Une erreur est survenue');
                    }
                })
                .catch(error => {
                    console.error('Erreur lors de la requête:', error);
                    alert(error.message || 'Une erreur est survenue lors de la création de la réservation');
                });
            }
        }
    });
}); 