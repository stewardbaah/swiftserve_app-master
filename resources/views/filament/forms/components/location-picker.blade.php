<div class="space-y-2">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

    <div
        x-data="{
            map: null,
            marker: null,
            async fetchAddress(lat, lng) {
                try {
                    const response = await fetch(`https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=${lat}&lon=${lng}`, {
                        headers: {
                            'Accept-Language': 'en'
                        }
                    });
                    if (response.ok) {
                        const data = await response.json();
                        if (data && data.display_name) {
                            const addressInput = document.getElementById('restaurant-address');
                            if (addressInput) {
                                addressInput.value = data.display_name;
                                addressInput.dispatchEvent(new Event('input'));
                                addressInput.dispatchEvent(new Event('change'));
                            }
                        }
                    }
                } catch (error) {
                    console.error('Error fetching address:', error);
                }
            },
            initMap() {
                if (typeof L === 'undefined') {
                    setTimeout(() => this.initMap(), 100);
                    return;
                }

                setTimeout(() => {
                    const defaultLat = 5.6037; // Accra
                    const defaultLng = -0.1870;

                    const latInput = document.getElementById('restaurant-latitude');
                    const lngInput = document.getElementById('restaurant-longitude');

                    let lat = latInput && latInput.value ? parseFloat(latInput.value) : defaultLat;
                    let lng = lngInput && lngInput.value ? parseFloat(lngInput.value) : defaultLng;

                    if (isNaN(lat)) lat = defaultLat;
                    if (isNaN(lng)) lng = defaultLng;

                    if (this.map) {
                        this.map.remove();
                    }

                    this.map = L.map(this.$refs.mapContainer).setView([lat, lng], 13);

                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        attribution: '&copy; OpenStreetMap'
                    }).addTo(this.map);

                    this.marker = L.marker([lat, lng], { draggable: true }).addTo(this.map);

                    const updateCoordinates = (latVal, lngVal) => {
                        if (latInput && lngInput) {
                            latInput.value = parseFloat(latVal).toFixed(8);
                            lngInput.value = parseFloat(lngVal).toFixed(8);
                            latInput.dispatchEvent(new Event('input'));
                            latInput.dispatchEvent(new Event('change'));
                        }
                    };

                    this.marker.on('dragend', (e) => {
                        const position = e.target.getLatLng();
                        updateCoordinates(position.lat, position.lng);
                        this.map.panTo(position);
                        this.fetchAddress(position.lat, position.lng);
                    });

                    this.map.on('click', (e) => {
                        const position = e.latlng;
                        this.marker.setLatLng(position);
                        updateCoordinates(position.lat, position.lng);
                        this.map.panTo(position);
                        this.fetchAddress(position.lat, position.lng);
                    });

                    if (latInput && lngInput) {
                        const handleInputChange = () => {
                            const lVal = parseFloat(latInput.value);
                            const lnVal = parseFloat(lngInput.value);
                            if (!isNaN(lVal) && !isNaN(lnVal)) {
                                const newPos = [lVal, lnVal];
                                this.marker.setLatLng(newPos);
                                this.map.setView(newPos, this.map.getZoom());
                            }
                        };
                        latInput.addEventListener('input', handleInputChange);
                        lngInput.addEventListener('input', handleInputChange);
                    }

                    // Force layout calculation for modal rendering
                    this.map.invalidateSize();
                }, 250);
            }
        }"
        x-init="initMap()"
        class="w-full relative"
        style="min-height: 350px;"
    >
        <div x-ref="mapContainer" class="w-full rounded-lg border border-gray-300 dark:border-gray-700 shadow-sm" style="height: 350px; z-index: 1;"></div>
    </div>
</div>
