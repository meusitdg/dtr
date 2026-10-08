<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$clientId = 'EJblaTEWpZivLpygsT4p7EtTyPSoErY1VgOHEkys';
$clientSecret = 'Iszt53jZZlKnpGfFEdrdrqvWaIZL2JsHKWbMPhzV4V62RtFdGnkHoefjtJobFtbpxiXRbPYw2k24dmjj162GGLz0tOapgGYGCy7iBUJSCHAeqfsbT0TBUdjblHPy0BPI';

$transactionId = $_GET['id'] ?? '';

if (!$transactionId) {
    http_response_code(400);
    echo json_encode(['error' => 'id não informado']);
    exit;
}

$tokenUrl = 'https://api.cakto.com.br/public_api/token/';
$tokenData = [
    'client_id' => $clientId,
    'client_secret' => $clientSecret
];

$ch = curl_init($tokenUrl);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($tokenData));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
$tokenResponse = curl_exec($ch);
$tokenHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($tokenHttpCode !== 200) {
    http_response_code(500);
    echo json_encode(['error' => 'Falha na autenticação Cakto']);
    exit;
}

$tokenInfo = json_decode($tokenResponse, true);
$accessToken = $tokenInfo['access_token'] ?? '';

if (!$accessToken) {
    http_response_code(500);
    echo json_encode(['error' => 'Token de acesso não recebido']);
    exit;
}

$orderUrl = 'https://api.cakto.com.br/public_api/orders/' . urlencode($transactionId) . '/';

$ch = curl_init($orderUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $accessToken,
    'Content-Type: application/json'
]);
$orderResponse = curl_exec($ch);
$orderHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($orderHttpCode !== 200) {
    http_response_code($orderHttpCode);
    echo json_encode(['error' => 'Pedido não encontrado', 'status_code' => $orderHttpCode]);
    exit;
}

$orderData = json_decode($orderResponse, true);

echo json_encode([
    'id' => $orderData['id'] ?? $transactionId,
    'status' => $orderData['status'] ?? 'unknown',
    'paymentMethod' => $orderData['paymentMethod'] ?? null,
    'amount' => $orderData['amount'] ?? null,
    'baseAmount' => $orderData['baseAmount'] ?? null,
    'fees' => $orderData['fees'] ?? null,
    'createdAt' => $orderData['createdAt'] ?? null,
    'product' => $orderData['product'] ?? null,
    'offer' => $orderData['offer'] ?? null
]);
