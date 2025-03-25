import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static values = {
        apiKey: String
    }

    connect() {
        if (document.getElementById('map') && !window.google?.maps) {
            if (!document.querySelector('script[src*="maps.googleapis.com"]')) {
                window.initMap = () => {
                    const map = new google.maps.Map(document.getElementById('map'), {
                        center: { lat: 47.8582, lng: -2.6651 }, 
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

                    // Créer un marker avec AdvancedMarkerElement
                    const marker = new google.maps.marker.AdvancedMarkerElement({
                        map,
                        position: { lat: 47.8582, lng: -2.6651 },
                        title: 'regards singuliers',
                        // Optionnel : personnaliser l'apparence du marker
                        content: this.createMarkerContent()
                    });
                };

                const script = document.createElement('script');
                // Ajouter le library 'marker' pour les AdvancedMarkerElement
                script.src = `https://maps.googleapis.com/maps/api/js?key=${this.apiKeyValue}&callback=initMap&libraries=marker`;
                script.async = true;
                script.defer = true;
                document.head.appendChild(script);
            }
        }
    }

    // Méthode optionnelle pour personnaliser le marker
    createMarkerContent() {
        const markerElement = document.createElement('div');
        markerElement.innerHTML = `
            <div style="
                background-color: #FF0000; 
                width: 20px; 
                height: 20px; 
                border-radius: 50%; 
                border: 2px solid white;
                box-shadow: 0 2px 4px rgba(0,0,0,0.2);
            "></div>
        `;
        return markerElement;
    }

    disconnect() {
        delete window.initMap;
    }
}