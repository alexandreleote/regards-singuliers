import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static values = {
        apiKey: String
    }

    connect() {
        // Vérifier qu'on est sur la page avec la carte
        if (document.getElementById('map')) {
            // Charger l'API Google Maps
            const script = document.createElement('script');
            script.src = `https://maps.googleapis.com/maps/api/js?key=${this.apiKeyValue}&callback=initMap`;
            script.async = true;
            script.defer = true;
            document.head.appendChild(script);
        }
    }
} 