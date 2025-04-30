/**
* Función para cargar las reservas disponibles desde la API
*/
// Elementos del DOM
const reservasContainer = document.getElementById('reservas-container');
const API_RESERVAR = 'api/reservar.php';
document.addEventListener('DOMContentLoaded', cargarReservas);

function cargarReservas() {
    // Mostrar indicador de carga
    reservasContainer.innerHTML = '<p>Cargando reservas realizadas...</p>';
    // Realizar solicitud a la API
    fetch(API_RESERVAR)
        .then(response => {
            if (!response.ok) {
                throw new Error(`Error ${response.status}: ${response.statusText}`);
            }
            return response.json();
        })
        .then(data => { //data contiene reservas.json
            if (data.status === 'success') {
                // Mostrar reservas en el reservas
                mostrarReservas(data.data);
            } else {
                // Mostrar mensaje de error
                reservasContainer.innerHTML = `<p class="error">Error al cargar reservas:
${data.message}</p>`;
            }
        })
        .catch(error => {
            // Manejar errores de red u otros errores
            reservasContainer.innerHTML = `<p class="error">Error al conectar con el servidor: ${error.message}</p>`;
            console.error('Error:', error);
        });
}

/**
* Función para mostrar las reservas disponibles
* @param {Object} data - Datos de reservas recibidos de la API
*/
function mostrarReservas(data) {
    console.log("aqui: ",data);
    // Crear tabla para el reservas
    let contenidoHTML = `
        <table class="reservas">
        <thead>
        <tr>
            <td><b>Nombre<b></td>
            <td><b>Email</b></td>
            <td><b>Telefono</b></td>
            <td><b>Motivo</b></td>
            <td><b>Fecha</b></td>
            <td><b>Hora</b></td>
        </tr>
        </thead>
        <tbody>
        `;
    // Crear filas para cada hora
    
    data.data.forEach(reserva => {
        contenidoHTML += `<tr>`;
        contenidoHTML += `<td>${reserva.nombre}</td>`;
        contenidoHTML += `<td>${reserva.email}</td>`;
        contenidoHTML += `<td>${reserva.telefono}</td>`;
        contenidoHTML += `<td>${reserva.motivo}</td>`;
        contenidoHTML += `<td>${formatearFecha(reserva.fecha)}</td>`;
        contenidoHTML += `<td>${reserva.hora}</td>`;
        contenidoHTML += '</tr>';
    }
    );
    contenidoHTML += '</tbody></table>';
    // Mostrar el reservas en el contenedor
    reservasContainer.innerHTML = contenidoHTML;
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