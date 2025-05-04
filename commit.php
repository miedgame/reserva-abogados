<?php

require_once 'vendor/autoload.php'; // Asegúrate de incluir esto
use Transbank\Webpay\WebpayPlus\Transaction;
session_start(); // Si guardaste datos en sesión, inicia la sesión

$token = $_POST['token_ws'] ?? $_GET['token_ws'] ?? null;
$tbkToken = $_POST['TBK_TOKEN'] ?? null; // Para flujos de anulación o abandono
$ordenCompra = $_POST['TBK_ORDEN_COMPRA'] ?? null;
$idSession = $_POST['TBK_ID_SESION'] ?? null;

// --- Flujo Normal (Usuario completó el pago o lo canceló explícitamente) ---
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirmación de Reserva</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
    
<div class="container">
    <header>
        <h1>Estudio Jurídico - Sistema de Reserva de Citas</h1>
    </header>
    <main>
    <section id="instrucciones">
                <h2>Confirmació de su reserva</h2>
                
            
<?php
if ($token && !$tbkToken) {
    try {
        // Instanciar la transacción
        $tx = new Transaction();
        $response = $tx->commit($token); // Confirmar la transacción con el token
        $mensaje="<p>Tu reserva no fue procesada, por favor ";
        $mensaje.="<a href='index.php'>Intenta nuevamente</a></p>";

        // --- Procesar la respuesta de la confirmación ---
        if ($response->isApproved()) {
            // ¡Pago APROBADO!
            echo "<h2>Pago Aprobado</h2>";
            echo "<p>ID de su Reserva: " . htmlspecialchars($response->getBuyOrder() ?? '') . "</p>";
            echo "<p>Monto: $" . htmlspecialchars($response->getAmount() ?? '') . "</p>";
            echo "<p>Código de Autorización: " . htmlspecialchars($response->getAuthorizationCode() ?? '') . "</p>";
            // ...existing code...
            // var_dump($_SESSION['datos']); // Para depuración
            $datos=array_merge($_SESSION['datos'], [
                'amount' => htmlspecialchars($response->getAmount() ?? ''),
                'transactionDate' => $response->getTransactionDate(),
                'estatus_pago' => 'Aprobado',
            ]);
            $reservar=realizarReserva($datos);
            // var_dump($reservar); // Para depuración
            if($reservar['status'] == 'success') {
                echo "<p>Reserva realizada con éxito.</p>";
                echo "<p>¿Deseas realizar otra reserva? ";
                echo "<a href='index.php'>Realizar otra reserva</a></p>";
            } else {
                echo "<p>Error al realizar la reserva: " . htmlspecialchars($reservar['message'] ?? '') . " . Pero el pago fue procesado, por favor comuniquese al siguiente correo electronico: hola@reservasabogados.com</p>";
                echo $mensaje;
            }	
            // var_dump($_SESSION['datos']);
            // var_dump($reservar); // Para depuración
            
            // Aquí deberías:
            // 1. Validar que $response->getBuyOrder() y $response->getAmount() coincidan con lo que tienes guardado para $token.
            // 2. Marcar tu orden de compra ($response->getBuyOrder()) como PAGADA en tu base de datos.
            // 3. Mostrar una página de éxito al usuario.
            // ¡Importante! Evita marcar como pagada si la transacción ya fue confirmada antes para este token.

        } else {
            // Pago RECHAZADO o Anulado por el usuario
            echo "<h2>Pago Rechazado</h2>";
            // echo "<p>Orden de Compra: " . htmlspecialchars($response->getBuyOrder() ?? '') . "</p>";
            // echo "<p>Respuesta de Transbank: " . htmlspecialchars($response->getResponseCode() ?? '') . "</p>";
            echo $mensaje;

             // Aquí deberías:
            // 1. Marcar tu orden de compra como RECHAZADA o PENDIENTE.
            // 2. Mostrar una página de error/rechazo al usuario.
            // var_dump($response); // Para depuración
        }

    } catch (\Exception $e) {
        // Error al confirmar la transacción
        echo "Error al confirmar la transacción con Webpay: " . $e->getMessage();
        echo $mensaje;
        // Marcar la orden como PENDIENTE o con ERROR.
        // Loggear el error $e->getMessage()
    }
}
// --- Flujo de Abandono o Timeout ---
// El usuario cerró el navegador o expiró el tiempo en Webpay, Transbank notifica via POST
else if (!$token && $tbkToken) {
     echo "<h2>Transacción Abortada</h2>";
     echo "<p>El pago fue anulado por tiempo de espera o por el usuario.</p>";
     echo $mensaje;

     // Aquí puedes marcar la orden como ABANDONADA.
}
// --- Caso Inválido ---
else {
    echo "<h1>Error al realizar su reserva</h1>";
    echo "<p>Parámetros inválidos recibidos desde Webpay.</p>";
    echo $mensaje;

    // No se recibió un token esperado. Podría ser un acceso directo a la URL.
}
?>
    </section>
</main>
        <footer>
            <p>&copy; 2025 Estudio Jurídico - Todos los derechos reservados</p>
        </footer>
</div>
</body>
</html>
<?php
// Siempre es buena idea limpiar los datos de sesión de webpay si los usaste
unset($_SESSION['webpay_token']);
unset($_SESSION['webpay_buyOrder']);
unset($_SESSION['datos']);

function realizarReserva($datos)
{
    // Enviar datos a reservar.php mediante cURL
    if (isset($datos)) {
        $url = 'http://localhost/node/reservas-abogados/api/reservar.php'; // URL del archivo reservar.php
        $ch = curl_init($url);

        // Configurar cURL
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($datos));

        // Ejecutar la solicitud
        $response = curl_exec($ch);

        // Manejar errores de cURL
        if (curl_errno($ch)) {
            echo "Error en cURL: " . curl_error($ch);
            return false;
        } 
        // Cerrar cURL
        curl_close($ch);
        return json_decode($response, true);
    }
}