import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static values = {
        apiKey: String
    }

    connect() {
        // Ensure the map element exists and Google Maps isn't already loaded
        if (document.getElementById('map')) {
            // Force reload of Google Maps API to ensure it's properly initialized
            if (!document.querySelector('script[src*="maps.googleapis.com"]')) {
                window.initMap = () => {
                    // Coordonnées de Saint-Aignan (6 Le Bronz 56480 Saint-Aignan)
                    const studioLocation = { lat: 48.0686, lng: -2.9630 };
                    
                    const map = new google.maps.Map(document.getElementById('map'), {
                        center: studioLocation,
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

                    // Créer un marqueur personnalisé pour le studio
                    const marker = new google.maps.Marker({
                        map,
                        position: studioLocation,
                        title: 'Regards Singuliers',
                        animation: google.maps.Animation.DROP
                    });
                    
                    // Ajouter une info window au marqueur
                    const infoWindow = new google.maps.InfoWindow({
                        content: '<div style="padding: 10px; max-width: 200px;"><strong>Regards Singuliers</strong><br>6 Le Bronz<br>56480 Saint-Aignan</div>'
                    });
                    
                    marker.addListener('click', () => {
                        infoWindow.open(map, marker);
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