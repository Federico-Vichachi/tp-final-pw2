// Parámetros de la ruleta
const canvas = document.getElementById("ruleta");
const ctx = canvas.getContext("2d");
const numSegments = categorias.length;
const segmentAngle = 2 * Math.PI / numSegments;
const radius = canvas.width / 2;

let rotation = 0;

// Función para dibujar la ruleta
function drawRuleta() {
    // Fondo blanco para mejor contraste
    ctx.fillStyle = '#ffffff';
    ctx.fillRect(0, 0, canvas.width, canvas.height);

    for (let i = 0; i < numSegments; i++) {
        const angleStart = i * segmentAngle + rotation;
        const angleEnd = (i + 1) * segmentAngle + rotation;

        ctx.fillStyle = categorias[i].color;
        ctx.beginPath();
        ctx.arc(radius, radius, radius - 10, angleStart, angleEnd);
        ctx.lineTo(radius, radius);
        ctx.closePath();
        ctx.fill();

        // Borde blanco entre segmentos
        ctx.strokeStyle = '#ffffff';
        ctx.lineWidth = 2;
        ctx.stroke();

        ctx.fillStyle = "#fff";
        ctx.font = "bold 14px Arial";
        ctx.textAlign = "center";
        ctx.textBaseline = "middle";
        ctx.save();
        ctx.translate(radius, radius);
        ctx.rotate((angleStart + angleEnd) / 2);
        ctx.fillText(categorias[i].nombre, radius - 60, 0);
        ctx.restore();
    }

    // Centro de la ruleta
    ctx.fillStyle = '#f8f9fa';
    ctx.beginPath();
    ctx.arc(radius, radius, 15, 0, 2 * Math.PI);
    ctx.fill();
    ctx.strokeStyle = '#dee2e6';
    ctx.lineWidth = 3;
    ctx.stroke();
}

// Función para girar la ruleta automáticamente
function girarRuletaAutomaticamente() {
    const spins = 5;
    const targetRotation = Math.random() * Math.PI * 2;
    const rotationIncrement = (Math.PI * 2 * spins + targetRotation - rotation) / 100;
    let currentRotation = rotation;
    let count = 0;

    // Mostrar spinner de carga
    document.getElementById('cargando').style.display = 'block';

    const animation = setInterval(() => {
        count++;
        currentRotation += rotationIncrement;
        rotation = currentRotation % (Math.PI * 2);
        drawRuleta();

        if (count >= 100) {
            clearInterval(animation);

            // Ocultar spinner
            document.getElementById('cargando').style.display = 'none';

            // Usar la categoría seleccionada que viene de PHP
            document.getElementById('mensajeCategoria').innerHTML =
                "¡Categoría seleccionada: <strong>" + categoriaSeleccionada + "</strong>!";
            document.getElementById('mensajeCategoria').style.display = 'block';

            // Redirigir automáticamente después de 1.5 segundos
            setTimeout(() => {
                window.location.href = '/game/jugarPartida';
            }, 1500);
        }
    }, 20);
}

// Inicializar y girar automáticamente después de un pequeño delay
drawRuleta();
setTimeout(girarRuletaAutomaticamente, 1000);