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
                        center: { lat: 48.0686, lng: -2.9630 }, // Coordonnées de Pontivy
                        zoom: 8,
                        styles: [
                            {
                                "featureType": "water",
                                "elementType": "geometry",
                                "stylers": [
                                    {
                                        "color": "#a3ccff"
                                    }
                                ]
                            },
                            {
                                "featureType": "landscape",
                                "elementType": "geometry",
                                "stylers": [
                                    {
                                        "color": "#f5f5f5"
                                    }
                                ]
                            },
                            {
                                "featureType": "road",
                                "elementType": "geometry",
                                "stylers": [
                                    {
                                        "visibility": "simplified"
                                    },
                                    {
                                        "color": "#ffffff"
                                    }
                                ]
                            },
                            {
                                "featureType": "poi",
                                "stylers": [
                                    {
                                        "visibility": "off"
                                    }
                                ]
                            },
                            {
                                "featureType": "administrative.province",
                                "elementType": "geometry.stroke",
                                "stylers": [
                                    {
                                        "color": "#c4c4c4"
                                    }
                                ]
                            },
                            {
                                "featureType": "administrative.country",
                                "elementType": "geometry.stroke",
                                "stylers": [
                                    {
                                        "color": "#a0a0a0"
                                    },
                                    {
                                        "weight": 1.5
                                    }
                                ]
                            },
                            {
                                "featureType": "administrative.locality",
                                "elementType": "labels.text",
                                "stylers": [
                                    {
                                        "visibility": "on"
                                    },
                                    {
                                        "weight": 0.5
                                    }
                                ]
                            },
                            {
                                "featureType": "road",
                                "elementType": "labels",
                                "stylers": [
                                    {
                                        "visibility": "off"
                                    }
                                ]
                            },
                            {
                                "featureType": "transit",
                                "stylers": [
                                    {
                                        "visibility": "off"
                                    }
                                ]
                            },
                            {
                                "featureType": "administrative",
                                "elementType": "labels",
                                "stylers": [
                                    {
                                        "visibility": "on"
                                    }
                                ]
                            }
                        ],
                        draggable: false,
                        zoomControl: false,
                        scrollwheel: false,
                        disableDoubleClickZoom: true,
                        streetViewControl: false,
                        mapTypeControl: false,
                        fullscreenControl: false,
                        gestureHandling: 'none',
                        keyboardShortcuts: false
                    });

                    // Créer un marqueur standard (pin)
                    new google.maps.Marker({
                        position: { lat: 48.0686, lng: -2.9630 }, // Coordonnées de Pontivy
                        map: map,
                        title: 'regards singuliers'
                    });
                };

                const script = document.createElement('script');
                script.src = `https://maps.googleapis.com/maps/api/js?key=${this.apiKeyValue}&callback=initMap&libraries=places&loading=async`;
                script.async = true;
                script.defer = true;
                document.head.appendChild(script);
            }
        }
    }

    disconnect() {
        delete window.initMap;
    }
}