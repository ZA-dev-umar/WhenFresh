// js/map.js
class MapManager {
    constructor(containerId) {
        this.map = null;
        this.markers = [];
        this.containerId = containerId;
    }

    initialize() {
        mapboxgl.accessToken = config.MAPBOX_TOKEN;
        this.map = new mapboxgl.Map({
            container: this.containerId,
            style: 'mapbox://styles/mapbox/light-v10',
            center: [-74.0060, 40.7128],
            zoom: 12
        });

        this.map.addControl(new mapboxgl.NavigationControl());
        this.map.addControl(new mapboxgl.GeolocateControl({
            positionOptions: {
                enableHighAccuracy: true
            },
            trackUserLocation: true
        }));
    }

    addMarker(shop) {
        const [lng, lat] = shop.location.split(',');
        
        const marker = new mapboxgl.Marker()
            .setLngLat([parseFloat(lng), parseFloat(lat)])
            .setPopup(new mapboxgl.Popup().setHTML(`
                <div class="p-2">
                    <h3 class="font-semibold">${shop.name}</h3>
                    <p class="text-sm text-gray-600">${shop.address}</p>
                </div>
            `))
            .addTo(this.map);
        
        this.markers.push(marker);
    }

    clearMarkers() {
        this.markers.forEach(marker => marker.remove());
        this.markers = [];
    }
}