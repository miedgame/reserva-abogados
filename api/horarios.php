<?php
// Configuración inicial
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
// Función para leer el archivo JSON de horarios
function obtenerHorarios()
{
    // Ruta al archivo JSON
    $rutaArchivo = '../data/horarios.json';
    // Verificar si el archivo existe
    if (!file_exists($rutaArchivo)) {
        // Archivo no encontrado
        http_response_code(404);
        return json_encode([
            'status' => 'error',
            'message' => 'No se encontró el archivo de horarios'
        ]);
    }
    try {
        // Leer el contenido del archivo
        $contenido = file_get_contents($rutaArchivo);
        // Decodificar el JSON
        $datos = json_decode($contenido, true);
        // Verificar si la decodificación fue exitosa
        if ($datos === null) {
            throw new Exception('Error al decodificar el archivo JSON');
        }
        // Retornar los datos formateados
        return json_encode([
            'status' => 'success',
            'data' => $datos
        ]);
    } catch (Exception $e) {
        // Error al procesar el archivo
        http_response_code(500);
        return json_encode([
            'status' => 'error',
            'message' => 'Error al procesar los horarios: ' . $e->getMessage()
        ]);
    }
}
// Verificar método de solicitud
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Obtener y mostrar los horarios
    echo obtenerHorarios();
} else {
    // Método no permitido
    http_response_code(405);
    echo json_encode([
        'status' => 'error',
        'message' => 'Método no permitido'
    ]);
}
