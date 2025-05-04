<?php
// Carga las dependencias de Composer
require_once 'vendor/autoload.php';

use Transbank\Webpay\WebpayPlus\Transaction;
use Transbank\Webpay\Options;
//configuración de la transacción
define('API_KEY', '579B532A7440BB0C9079DED94D31EA1615BACEB56610332264630D42D0A36B1C');
define('COMMERCE_CODE', '597055555532');
$option = new Options(API_KEY, COMMERCE_CODE, Options::ENVIRONMENT_INTEGRATION);
$transaction = new Transaction($option);
$sessionId = session_id(); // Identificador único de la sesión de tu usuario
if (empty($sessionId)) {
    session_start(); // Asegúrate de que haya una sesión activa
    $sessionId = session_id();
    $_SESSION['datos']=$_POST; // Datos del formulario de reserva
}
$amount = 95000; // Monto en CLP (pesos chilenos)
$returnUrl = 'http://localhost/node/reservas-abogados/commit.php'; // URL a la que Transbank redirigirá al usuario
$buyOrder = 'OC-' . date('YmdHis') . rand(1000, 9999); // ¡Debe ser única!

try {
    $response = $transaction->create($buyOrder, $sessionId, $amount, $returnUrl);
    // Instanciar la transacción (usará la configuración global por defecto)
    // --- Procesar la respuesta de Transbank ---
    if ($response && $response->getToken() && $response->getUrl()) {
        // Guardar el token en algún lugar (ej. sesión, base de datos) asociado a $buyOrder
        $_SESSION['webpay_token'] = $response->getToken();
        $_SESSION['webpay_buyOrder'] = $buyOrder;
        // var_dump($_POST); // Para depuración
        // Redirigir al usuario al formulario de pago de Webpay
        header('Location: ' . $response->getUrl() . '?token_ws=' . $response->getToken());
        exit;
    } else {
        // Hubo un error al crear la transacción
        echo "Error al iniciar la transacción con Webpay.";
        // Aquí podrías loggear $response o manejar el error de otra forma
        var_dump($response); // Para depuración
    }

} catch (\Exception $e) {
    // Capturar cualquier excepción del SDK
    echo "Error al conectar con Webpay: " . $e->getMessage();
    // Loggear el error $e->getMessage()
}
