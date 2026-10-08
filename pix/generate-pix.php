<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$clientId = 'EJblaTEWpZivLpygsT4p7EtTyPSoErY1VgOHEkys';
$clientSecret = 'Iszt53jZZlKnpGfFEdrdrqvWaIZL2JsHKWbMPhzV4V62RtFdGnkHoefjtJobFtbpxiXRbPYw2k24dmjj162GGLz0tOapgGYGCy7iBUJSCHAeqfsbT0TBUdjblHPy0BPI';
$offerId = '8kbynaf_1180687';

$input = json_decode(file_get_contents('php://input'), true);

if (!$input || empty($input['cpf']) || empty($input['nome']) || empty($input['email'])) {
    http_response_code(400);
    echo json_encode(['result' => ['ok' => false, 'error' => 'Dados do cliente incompletos']]);
    exit;
}

$phone = preg_replace('/\D/', '', $input['phone'] ?? '');
if (strlen($phone) === 12) {
    $phone = '55' . $phone;
} elseif (strlen($phone) === 10) {
    $phone = '55' . $phone;
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
    echo json_encode(['result' => ['ok' => false, 'error' => 'Falha na autenticação Cakto']]);
    exit;
}

$tokenInfo = json_decode($tokenResponse, true);
$accessToken = $tokenInfo['access_token'] ?? '';

if (!$accessToken) {
    http_response_code(500);
    echo json_encode(['result' => ['ok' => false, 'error' => 'Token de acesso não recebido']]);
    exit;
}

$paymentUrl = 'https://api.cakto.com.br/public_api/payments/';
$paymentData = [
    'paymentMethod' => 'pix',
    'customer' => [
        'name' => $input['nome'],
        'email' => $input['email'],
        'phone' => $phone,
        'fingerprint' => 'fp_' . md5($input['cpf'] . time()),
        'docType' => 'cpf',
        'docNumber' => preg_replace('/\D/', '', $input['cpf'])
    ],
    'items' => [
        ['offerId' => $offerId]
    ],
    'pixExpiresIn' => 600
];

$idempotencyKey = sprintf(
    '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
    mt_rand(0, 0xffff), mt_rand(0, 0xffff),
    mt_rand(0, 0xffff),
    mt_rand(0, 0x0fff) | 0x4000,
    mt_rand(0, 0x3fff) | 0x8000,
    mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
);

$ch = curl_init($paymentUrl);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($paymentData));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $accessToken,
    'Content-Type: application/json',
    'X-Idempotency-Key: ' . $idempotencyKey
]);
$paymentResponse = curl_exec($ch);
$paymentHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$paymentData = json_decode($paymentResponse, true);

if ($paymentHttpCode === 201 && isset($paymentData['pix']['qrCode'])) {
    echo json_encode([
        'result' => [
            'ok' => true,
            'code' => $paymentData['pix']['qrCode'],
            'qrCode' => $paymentData['pix']['qrCode'],
            'txid' => $paymentData['id'],
            'expirationDate' => $paymentData['pix']['expirationDate'] ?? null
        ]
    ]);
} else {
    $errorMsg = $paymentData['detail'] ?? 'Erro ao criar cobrança Pix';
    http_response_code(500);
    echo json_encode(['result' => ['ok' => false, 'error' => $errorMsg]]);
}
