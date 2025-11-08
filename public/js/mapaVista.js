document.addEventListener("DOMContentLoaded", function() {
    const mapa = document.getElementById('map');
    const form = document.getElementById('registroForm');
    const ubicacionInfo = document.getElementById('ubicacion-info');

    // Si no hay mapa, salir
    if (!mapa) return;

    // Inicializar mapa
    const map = L.map('map').setView([20, 0], 2);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);

    // Marcador inicial
    const marker = L.marker([20, 0], {draggable: true}).addTo(map);

    // Variables para almacenar ubicación
    let ubicacionActual = {
        pais: 'No seleccionado',
        ciudad: 'No seleccionada',
        latitud: 0,
        longitud: 0
    };

    // Función principal para actualizar ubicación
    async function actualizarUbicacion(lat, lon) {
        // Actualizar coordenadas inmediatamente
        ubicacionActual.latitud = lat;
        ubicacionActual.longitud = lon;

        // Mostrar carga
        mostrarInfoUbicacion('Buscando ubicación...');

        try {
            // Llamada a Nominatim
            const response = await fetch(
                `https://nominatim.openstreetmap.org/reverse?lat=${lat}&lon=${lon}&format=json&addressdetails=1&zoom=10&accept-language=es`
            );

            if (!response.ok) throw new Error('Error en la API');

            const data = await response.json();
            procesarDatosUbicacion(data, lat, lon);

        } catch (error) {
            console.log('Error obteniendo ubicación:', error);
            // Fallback: usar coordenadas
            ubicacionActual.pais = 'Ubicación por coordenadas';
            ubicacionActual.ciudad = `Lat: ${lat.toFixed(4)}, Lon: ${lon.toFixed(4)}`;
            guardarUbicacion();
        }
    }

    // Procesar datos de la API
    function procesarDatosUbicacion(data, lat, lon) {
        const address = data.address || {};

        // Buscar país
        ubicacionActual.pais =
            address.country ||
            address.country_code ||
            'País no identificado';

        // Buscar ciudad con múltiples opciones
        ubicacionActual.ciudad =
            address.city ||
            address.town ||
            address.village ||
            address.municipality ||
            address.county ||
            address.state_district ||
            address.region ||
            'Área no urbana';

        guardarUbicacion();
    }

    // Guardar ubicación en formulario y mostrar
    function guardarUbicacion() {
        // Actualizar campos ocultos del formulario
        document.getElementById('pais').value = ubicacionActual.pais;
        document.getElementById('ciudad').value = ubicacionActual.ciudad;
        document.getElementById('latitud').value = ubicacionActual.latitud;
        document.getElementById('longitud').value = ubicacionActual.longitud;

        // Mostrar información al usuario
        mostrarInfoUbicacion(
            `${ubicacionActual.pais}, ${ubicacionActual.ciudad}<br>
             <small>Coordenadas: ${ubicacionActual.latitud.toFixed(4)}, ${ubicacionActual.longitud.toFixed(4)}</small>`
        );

        console.log('Ubicación guardada:', ubicacionActual);
    }

    // Mostrar información de ubicación
    function mostrarInfoUbicacion(mensaje) {
        ubicacionInfo.innerHTML = mensaje;
    }

    // Eventos del mapa
    marker.on('dragend', function(e) {
        const pos = marker.getLatLng();
        actualizarUbicacion(pos.lat, pos.lng);
    });

    map.on('click', function(e) {
        marker.setLatLng(e.latlng);
        actualizarUbicacion(e.latlng.lat, e.latlng.lng);
    });

    // Validación antes de enviar el formulario
    form.addEventListener('submit', function(e) {
        const pais = document.getElementById('pais').value;
        const ciudad = document.getElementById('ciudad').value;

        if (pais === 'No seleccionado' || ciudad === 'No seleccionada') {
            e.preventDefault();
            alert('Por favor, selecciona una ubicación en el mapa antes de enviar el formulario.');
            return false;
        }

        console.log('Enviando ubicación:', { pais, ciudad });
    });

    // Inicializar con ubicación por defecto (centro del mapa)
    setTimeout(() => {
        const center = map.getCenter();
        actualizarUbicacion(center.lat, center.lng);
    }, 500);
});