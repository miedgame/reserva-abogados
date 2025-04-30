<?php
// Configuración inicial
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET');
header('Access-Control-Allow-Headers: Content-Type');
// Función para validar los datos de reserva
function validarDatos($datos)
{
    // Verificar campos requeridos
    $camposRequeridos = ['nombre', 'email', 'telefono', 'motivo', 'fecha', 'hora'];
    foreach ($camposRequeridos as $campo) {
        if (!isset($datos[$campo]) || empty($datos[$campo])) {
            return [
                'valido' => false,
                'mensaje' => "El campo '{$campo}' es obligatorio"
            ];
        }
    }
    // Validar formato de email
    if (!filter_var($datos['email'], FILTER_VALIDATE_EMAIL)) {
        return [
            'valido' => false,
            'mensaje' => 'El formato del correo electrónico no es válido'
        ];
    }
    // Validar formato de fecha (YYYY-MM-DD)
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $datos['fecha'])) {
        return [
            'valido' => false,
            'mensaje' => 'El formato de fecha debe ser YYYY-MM-DD'
        ];
    }
    // Validar formato de hora (HH:MM)
    if (!preg_match('/^\d{2}:\d{2}$/', $datos['hora'])) {
        return [
            'valido' => false,
            'mensaje' => 'El formato de hora debe ser HH:MM'
        ];
    }
    // Si todas las validaciones pasan
    return [
        'valido' => true
    ];
}
// Función para verificar disponibilidad de la hora
function verificarDisponibilidad($fecha, $hora)
{
    // Ruta al archivo JSON de horarios
    $rutaArchivo = '../data/horarios.json';
    // Verificar si el archivo existe
    if (!file_exists($rutaArchivo)) {
        return [
            'disponible' => false,
            'mensaje' => 'No se encontró el archivo de horarios'
        ];
    }
    try {
        // Leer el contenido del archivo
        $contenido = file_get_contents($rutaArchivo);
        $horarios = json_decode($contenido, true);
        // Buscar la fecha en los datos
        foreach ($horarios['fechas'] as $diaInfo) {
            if ($diaInfo['fecha'] === $fecha) {
                // Buscar la hora en ese día
                foreach ($diaInfo['horarios'] as $horario) {
                    if ($horario['hora'] === $hora) {
                        // Verificar estado
                        if ($horario['estado'] === 'disponible') {
                            return ['disponible' => true];
                        } else {
                            return [
                                'disponible' => false,
                                'mensaje' => 'La hora seleccionada ya está reservada'
                            ];
                        }
                    }
                }
                // Si llega aquí, la hora no existe en ese día
                return [
                    'disponible' => false,
                    'mensaje' => 'La hora seleccionada no está disponible en ese día'
                ];
            }
        }
        // Si llega aquí, la fecha no existe
        return [
            'disponible' => false,
            'mensaje' => 'La fecha seleccionada no está disponible'
        ];
    } catch (Exception $e) {
        return [
            'disponible' => false,
            'mensaje' => 'Error al verificar disponibilidad: ' . $e->getMessage()
        ];
    }
}
// Función para guardar la reserva
function guardarReserva($datos)
{
    // Ruta al archivo JSON de reservas
    $rutaArchivo = '../data/reservas.json';
    try {
        // Generar ID único para la reserva
        $reservaId = uniqid();
        // Crear objeto de reserva con ID
        $nuevaReserva = [
            'id' => $reservaId,
            'nombre' => $datos['nombre'],
            'email' => $datos['email'],
            'telefono' => $datos['telefono'],
            'motivo' => $datos['motivo'],
            'fecha' => $datos['fecha'],
            'hora' => $datos['hora'],
            'fechaReserva' => date('Y-m-d H:i:s')
        ];
        // Verificar si el archivo de reservas existe
        if (file_exists($rutaArchivo)) {
            // Leer reservas existentes
            $contenido = file_get_contents($rutaArchivo);
            $reservas = json_decode($contenido, true);
            // Agregar nueva reserva
            $reservas[] = $nuevaReserva;
        } else {
            // Crear nuevo archivo de reservas
            $reservas = [$nuevaReserva];
        }
        // Guardar reservas actualizadas
        if (file_put_contents($rutaArchivo, json_encode($reservas, JSON_PRETTY_PRINT))) {
            // Actualizar estado de la hora a 'reservado'
            actualizarEstadoHorario($datos['fecha'], $datos['hora']);
            return [
                'exito' => true,
                'reservaId' => $reservaId
            ];
        } else {
            throw new Exception('No se pudo escribir en el archivo de reservas');
        }
    } catch (Exception $e) {
        return [
            'exito' => false,
            'mensaje' => 'Error al guardar la reserva: ' . $e->getMessage()
        ];
    }
}
// Función para actualizar el estado de la hora a 'reservado'
function actualizarEstadoHorario($fecha, $hora)
{
    // Ruta al archivo JSON de horarios
    $rutaArchivo = '../data/horarios.json';
    // Verificar si el archivo existe
    if (!file_exists($rutaArchivo)) {
        return false;
    }
    try {
        // Leer el contenido del archivo
        $contenido = file_get_contents($rutaArchivo);
        $horarios = json_decode($contenido, true);
        // Actualizar estado de la hora
        foreach ($horarios['fechas'] as &$diaInfo) {
            if ($diaInfo['fecha'] === $fecha) {
                foreach ($diaInfo['horarios'] as &$horario) {
                    if ($horario['hora'] === $hora) {
                        $horario['estado'] = 'reservado';
                        break;
                    }
                }
                break;
            }
        }
        // Guardar los cambios
        return file_put_contents($rutaArchivo, json_encode(
            $horarios,
            JSON_PRETTY_PRINT
        ));
    } catch (Exception $e) {
        return false;
    }
}

// función para obtener reservas realizadas
function obtenerReservas()
{
    // Ruta al archivo JSON de reservas
    $rutaArchivo = '../data/reservas.json';
    // Verificar si el archivo existe
    if (!file_exists($rutaArchivo)) {
        return [
            'status' => 'error',
            'message' => 'No se encontró el archivo de reservas'
        ];
    }
    try {
        // Leer el contenido del archivo
        $contenido = file_get_contents($rutaArchivo);
        $reservas = json_decode($contenido, true);
        // Retornar los datos formateados
        return [
            'status' => 'success',
            'data' => $reservas
        ];
    } catch (Exception $e) {
        // Error al procesar el archivo
        return [
            'status' => 'error',
            'message' => 'Error al procesar las reservas: ' . $e->getMessage()
        ];
    }
}

// Manejar solicitud POST para crear reserva
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Obtener datos de la solicitud
    $input = file_get_contents('php://input');
    $datos = json_decode($input, true);
    // Si no hay datos JSON, intentar obtener datos del formulario
    if ($datos === null) {
        $datos = $_POST;
    }
    // Validar datos recibidos
    $validacion = validarDatos($datos);
    if (!$validacion['valido']) {
        // Datos inválidos
        http_response_code(400);
        echo json_encode([
            'status' => 'error',
            'message' => $validacion['mensaje']
        ]);
        exit;
    }
    // Verificar disponibilidad de la hora
    $disponibilidad = verificarDisponibilidad($datos['fecha'], $datos['hora']);
    if (!$disponibilidad['disponible']) {
        // Hora no disponible
        http_response_code(409);
        echo json_encode([
            'status' => 'error',
            'message' => $disponibilidad['mensaje']
        ]);
        exit;
    }
    // Guardar la reserva
    $resultado = guardarReserva($datos);
    if ($resultado['exito']) {
        // Reserva exitosa
        http_response_code(201);
        echo json_encode([
            'status' => 'success',
            'message' => 'Reserva realizada con éxito',
            'reservaId' => $resultado['reservaId']
        ]);
    } else {
        // Error al guardar
        http_response_code(500);
        echo json_encode([
            'status' => 'error',
            'message' => $resultado['mensaje']
        ]);
    }
} else if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    // Responder a la solicitud preflight CORS
    http_response_code(200);
    exit;
} else if($_SERVER['REQUEST_METHOD'] === 'GET') {
    $input = file_get_contents('php://input');
    $datos = json_decode($input, true);
    http_response_code(201);//consulta de reservas exitosa
    echo json_encode([
        'status' => 'success',
        'message' => 'Consulta de reservas realizada con éxito',
        'data' => obtenerReservas()
    ]);
}else {
    // Método no permitido
    http_response_code(405);
    echo json_encode([
        'status' => 'error',
        'message' => 'Método no permitido'
    ]);
}
