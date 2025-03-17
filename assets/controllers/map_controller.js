import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static values = {
        apiKey: String
    }

    connect() {
        // Vérifier qu'on est sur la page avec la carte
        if (document.getElementById('map') && !window.google?.maps) {
            // Charger l'API Google Maps une seule fois
            if (!document.querySelector('script[src*="maps.googleapis.com"]')) {
                window.initMap = () => {
                    const map = new google.maps.Map(document.getElementById('map'), {
                        center: { lat: 47.8582, lng: -2.6651 }, // Coordonnées de Saint-Aignan
                        zoom: 15,
                        styles: [
                            {
                                featureType: 'all',
                                elementType: 'all',
                                stylers: [
                                    { saturation: -100 }
                                ]
                            }
                        ]
                    });

                    // Utiliser AdvancedMarkerElement au lieu de Marker
                    const marker = new google.maps.marker.AdvancedMarkerElement({
                        map,
                        position: { lat: 47.8582, lng: -2.6651 },
                        title: 'regards singuliers'
                    });
                };

                const script = document.createElement('script');
                script.src = `https://maps.googleapis.com/maps/api/js?key=${this.apiKeyValue}&callback=initMap`;
                script.async = true;
                script.defer = true;
                document.head.appendChild(script);
            }
        }
    }

    disconnect() {
        // Nettoyer la fonction initMap quand le contrôleur est déconnecté
        delete window.initMap;
    }
} 