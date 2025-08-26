<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido']);
    exit;
}

// Obtener datos del formulario
$input = json_decode(file_get_contents('php://input'), true);
$email = filter_var($input['email'] ?? '', FILTER_VALIDATE_EMAIL);
$language = $input['language'] ?? 'es';

if (!$email) {
    http_response_code(400);
    echo json_encode(['error' => 'Email inválido']);
    exit;
}

// Nombre del archivo donde se guardarán los emails
$filename = 'subscribers.txt';

// Verificar si el email ya existe
$existing_emails = [];
if (file_exists($filename)) {
    $existing_emails = file($filename, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
}

// Buscar si el email ya existe
$email_exists = false;
foreach ($existing_emails as $line) {
    $data = json_decode($line, true);
    if ($data && $data['email'] === $email) {
        $email_exists = true;
        break;
    }
}

if ($email_exists) {
    echo json_encode([
        'success' => false,
        'message' => $language === 'es' ? 'Este email ya está suscrito' : 'This email is already subscribed'
    ]);
    exit;
}

// Crear registro del suscriptor
$subscriber_data = [
    'email' => $email,
    'language' => $language,
    'date' => date('Y-m-d H:i:s'),
    'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
];

// Guardar en el archivo
$success = file_put_contents($filename, json_encode($subscriber_data) . "\n", FILE_APPEND | LOCK_EX);

if ($success) {
    echo json_encode([
        'success' => true,
        'message' => $language === 'es' ? 
            '¡Gracias por suscribirte! Te notificaremos cuando esté listo.' : 
            'Thank you for subscribing! We will notify you when it\'s ready.'
    ]);
} else {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $language === 'es' ? 
            'Error al procesar la suscripción. Inténtalo más tarde.' : 
            'Error processing subscription. Please try again later.'
    ]);
}
?>