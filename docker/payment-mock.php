<?php
// Fake mobile payment API for local tests.
// Phone ending in 0 = refused (no balance); wrong token = 401; anything else = paid.
header('Content-Type: application/json');
$body = json_decode(file_get_contents('php://input'), true) ?: [];
error_log('payment-mock received: '.json_encode($body));

if (($_SERVER['HTTP_AUTHORIZATION'] ?? '') !== 'Bearer '.getenv('EXPECTED_TOKEN')) {
    http_response_code(401);
    exit(json_encode(['detail' => 'Not authenticated']));
}

sleep(2); // pretend the customer is confirming on the phone

if (str_ends_with((string) ($body['phone_number'] ?? ''), '0')) {
    http_response_code(422);
    exit(json_encode(['detail' => 'Saldo insuficiente (simulação)']));
}

echo json_encode(['success' => true, 'transaction_id' => 'SIM'.strtoupper(bin2hex(random_bytes(4))), 'message' => 'Pagamento aceite (simulação)']);
