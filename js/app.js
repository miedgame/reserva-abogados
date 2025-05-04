// Elementos del DOM
const calendarioContainer = document.getElementById('calendario-container');
const formularioReserva = document.getElementById('reservaForm');
const campoFecha = document.getElementById('fecha');
const campoHora = document.getElementById('hora');
const btnReservar = document.getElementById('btnReservar');
const mensajeResultado = document.getElementById('mensaje-resultado');
// URL de los endpoints API
const API_HORARIOS = 'api/horarios.php';
const API_RESERVAR = 'api/reservar.php';
// Cargar horarios disponibles al cargar la página
document.addEventListener('DOMContentLoaded', cargarHorarios);
// Manejar envío del formulario
// formularioReserva.addEventListener('submit', realizarReserva);
/**
* Función para cargar los horarios disponibles desde la API
*/
function cargarHorarios() {
    // Mostrar indicador de carga
    calendarioContainer.innerHTML = '<p>Cargando horarios disponibles...</p>';
    // Realizar solicitud a la API
    fetch(API_HORARIOS)
        .then(response => {
            if (!response.ok) {
                throw new Error(`Error ${response.status}: ${response.statusText}`);
            }
            return response.json();
        })
        .then(data => { //data contiene horarios.json
            console.log(data);
            if (data.status === 'success') {
                // Mostrar horarios en el calendario
                mostrarCalendario(data.data);
            } else {
                // Mostrar mensaje de error
                calendarioContainer.innerHTML = `<p class="error">Error al cargar horarios:
${data.message}</p>`;
            }
        })
        .catch(error => {
            // Manejar errores de red u otros errores
            calendarioContainer.innerHTML = `<p class="error">Error al conectar con el
servidor: ${error.message}</p>`;
            console.error('Error:', error);
        });
}
/**
* Función para mostrar el calendario con los horarios disponibles
* @param {Object} data - Datos de horarios recibidos de la API
*/
function mostrarCalendario(data) {
    // Verificar si hay datos de fechas
    if (!data.fechas || data.fechas.length === 0) {
        calendarioContainer.innerHTML = '<p>No hay horarios disponibles en este momento.</p>';
        return;
    }
    // Crear tabla para el calendario
    let contenidoHTML = `
<table class="calendario">
<thead>
<tr>
<th>Hora</th>
${data.fechas.map(fecha =>
        `<th>${fecha.dia}<br>${formatearFecha(fecha.fecha)}</th>`).join('')}
</tr>
</thead>
<tbody>
`;
    // Obtener todas las horas posibles (9:00 a 17:00, excepto 13:00)
    const horasPosibles = ['09:00', '10:00', '11:00', '12:00', '14:00', '15:00', '16:00'];
    // Crear filas para cada hora
    horasPosibles.forEach(hora => {
        contenidoHTML += `<tr><td>${hora}</td>`;
        // Para cada fecha, verificar disponibilidad de la hora
        data.fechas.forEach(fecha => {
            const horario = fecha.horarios.find(h => h.hora === hora);
            const estado = horario ? horario.estado : 'no-disponible';
            const claseEstado = estado === 'disponible' ? 'disponible' : 'reservado';
            const esClickeable = estado === 'disponible' ?
                `onclick="seleccionarHorario('${fecha.fecha}', '${hora}')"` : '';
            contenidoHTML += `<td class="${claseEstado}" ${esClickeable}>${estado ===
                'disponible' ? 'Disponible' : 'Reservado'}</td>`;
        });
        contenidoHTML += '</tr>';
    });
    contenidoHTML += '</tbody></table>';
    // Mostrar el calendario en el contenedor
    calendarioContainer.innerHTML = contenidoHTML;
}
/**
* Función para formatear la fecha en formato legible
* @param {string} fechaISO - Fecha en formato YYYY-MM-DD
* @return {string} - Fecha formateada (DD/MM)
*/
function formatearFecha(fechaISO) {
    const fecha = new Date(fechaISO);
    const dia = fecha.getDate().toString().padStart(2, '0');
    const mes = (fecha.getMonth() + 1).toString().padStart(2, '0');
    return `${dia}/${mes}`;
}
/**
* Función para seleccionar un horario del calendario
* @param {string} fecha - Fecha seleccionada
* @param {string} hora - Hora seleccionada
*/
function seleccionarHorario(fecha, hora) {
    // Actualizar campos del formulario
    campoFecha.value = fecha;
    campoHora.value = hora;
    // Habilitar botón de reserva
    btnReservar.disabled = false;
    // Resaltar celda seleccionada en el calendario
    const celdas = document.querySelectorAll('.calendario td.disponible');
    celdas.forEach(celda => celda.classList.remove('seleccionado'));
    // Encontrar la celda correspondiente a la fecha y hora seleccionadas
    const celdaSeleccionada = Array.from(celdas).find(
        celda => celda.parentNode.cells[0].textContent === hora &&
            celda.parentNode.cells[Array.from(celda.parentNode.cells).indexOf(celda)].getAttribute('onclick').includes(fecha)
    );
    if (celdaSeleccionada) {
        celdaSeleccionada.classList.add('seleccionado');
    }
    // Desplazarse al formulario
    formularioReserva.scrollIntoView({ behavior: 'smooth' });
}
/**
* Función para realizar la reserva
* @param {Event} event - Evento de envío del formulario
*/
function realizarReserva(event) {
    // Prevenir comportamiento por defecto del formulario
    event.preventDefault();
    // Obtener datos del formulario
    const formData = new FormData(formularioReserva);
    const datosReserva = {
        nombre: formData.get('nombre'),
        email: formData.get('email'),
        telefono: formData.get('telefono'),
        motivo: formData.get('motivo'),
        fecha: formData.get('fecha'),
        hora: formData.get('hora'),
        amount: formData.get('amount')
    };
    // Deshabilitar botón para evitar envíos múltiples
    btnReservar.disabled = true;
    btnReservar.textContent = 'Procesando...';
    // Enviar datos a la API
    fetch(API_RESERVAR, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(datosReserva)
    })
        .then(response => {
            // Obtener datos de la respuesta
            return response.json().then(data => {
                return {
                    status: response.status,
                    ok: response.ok,
                    data: data
                };
            });
        })
        .then(result => {
            if (result.ok) {
                // Reserva exitosa
                mostrarMensajeResultado('success', `¡Reserva exitosa! Su número de reserva es:
${result.data.reservaId}`);
                // Limpiar formulario
                formularioReserva.reset();
                // Recargar horarios para actualizar disponibilidad
                setTimeout(cargarHorarios, 1000);
            } else {
                // Error en la reserva
                mostrarMensajeResultado('error', `Error: ${result.data.message}`);
            }
            // Restaurar botón
            btnReservar.disabled = false;
            btnReservar.textContent = 'Reservar hora';
        })
        .catch(error => {
            // Manejar errores de red u otros errores
            mostrarMensajeResultado('error', `Error de conexión: ${error.message}`);
            console.error('Error:', error);
            // Restaurar botón
            btnReservar.disabled = false;
            btnReservar.textContent = 'Reservar hora';
        });
}
/**
* Función para mostrar mensaje de resultado
* @param {string} tipo - Tipo de mensaje ('success' o 'error')
* @param {string} mensaje - Texto del mensaje
*/
function mostrarMensajeResultado(tipo, mensaje) {
    // Configurar mensaje
    mensajeResultado.textContent = mensaje;
    mensajeResultado.className = tipo;
    mensajeResultado.classList.remove('oculto');
    // Desplazarse al mensaje
    mensajeResultado.scrollIntoView({ behavior: 'smooth' });
    // Ocultar mensaje después de un tiempo
    setTimeout(() => { mensajeResultado.classList.add('oculto'); }, 5000);
}