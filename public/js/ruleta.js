// Parámetros de la ruleta
const canvas = document.getElementById("ruleta");
const ctx = canvas.getContext("2d");
const numSegments = categorias.length;
const segmentAngle = 2 * Math.PI / numSegments;
const radius = canvas.width / 2;
const btnGirar = document.getElementById("btnGirar");

let rotation = 0;  // Ángulo de rotación inicial

// Función para dibujar la ruleta
function drawRuleta() {
    // Dibujar cada segmento de la ruleta
    for (let i = 0; i < numSegments; i++) {
        const angleStart = i * segmentAngle + rotation;
        const angleEnd = (i + 1) * segmentAngle + rotation;

        // Configurar color y texto del segmento
        ctx.fillStyle = categorias[i].color;
        ctx.beginPath();
        ctx.arc(radius, radius, radius, angleStart, angleEnd);
        ctx.lineTo(radius, radius);
        ctx.closePath();
        ctx.fill();

        // Dibujar el texto (nombre de la categoría)
        ctx.fillStyle = "#fff";
        ctx.font = "20px Arial";
        ctx.textAlign = "center";
        ctx.textBaseline = "middle";
        ctx.save();
        ctx.translate(radius, radius);
        ctx.rotate((angleStart + angleEnd) / 2);
        ctx.fillText(categorias[i].nombre, radius - 50, 0);
        ctx.restore();
    }
}

// Función para girar la ruleta
function spinRuleta() {
    const spins = Math.floor(Math.random() * 5) + 5;  // Número de giros aleatorios
    const targetRotation = Math.random() * Math.PI * 2;  // Ángulo de destino aleatorio
    const rotationIncrement = (Math.PI * 2 * spins + targetRotation - rotation) / 100; // Incremento en cada paso
    let currentRotation = rotation;
    let count = 0;
    // Animación de giro
    const animation = setInterval(() => {
        count++;
        currentRotation += rotationIncrement;
        rotation = currentRotation % (Math.PI * 2);  // Mantener la rotación entre 0 y 2π
        drawRuleta();
        if (count >= 100) {
            clearInterval(animation);
            // Determinar la categoría ganadora
            const finalAngle = (rotation % (2 * Math.PI)) / segmentAngle;
            const winningSegment = Math.floor(finalAngle);
            const categoriaGanadora = categorias[winningSegment];

            // Mostrar mensaje en la página
            document.getElementById('mensajeCategoria').innerText = "¡Categoría ganadora: " + categoriaGanadora.nombre + "!";
            document.getElementById('mensajeCategoria').style.display = 'block';

            // Ocultar botón Girar y mostrar Proceder
            document.getElementById('btnGirar').style.display = 'none';
            document.getElementById('btnProceder').style.display = 'block';

            // Configurar el botón Proceder para redirigir
            document.getElementById('btnProceder').onclick = function() {
                window.location.href = '/game/jugarPartida?categoria=' + encodeURIComponent(categoriaGanadora.nombre);
            };
        }
    }, 20);  // Actualizar cada 20ms
}


// Inicializar la ruleta
drawRuleta();

// Agregar el evento de clic al botón de "Girar"
btnGirar.addEventListener("click", spinRuleta);