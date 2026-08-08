
<?php
header('Content-Type: application/json; charset=utf-8');

require __DIR__ . '/../config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$input  = json_decode(file_get_contents('php://input'), true);
$prompt = isset($input['prompt']) ? trim($input['prompt']) : '';

if ($prompt === '') {
    http_response_code(400);
    echo json_encode(['error' => 'Please send valid text in the prompt field']);
    exit;
}

if (!defined('COHERE_API_KEY') || COHERE_API_KEY === '' || COHERE_API_KEY === 'PASTE_YOUR_COHERE_KEY_HERE') {
    http_response_code(500);
    echo json_encode(['error' => 'Cohere API key not set in config.php']);
    exit;
}

$model = 'command-a-03-2025';
$url   = 'https://api.cohere.com/v2/chat';

$body = json_encode([
    'model' => $model,
    'messages' => [
        ['role' => 'user', 'content' => $prompt]
    ]
]);

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_HTTPHEADER     => [
        'Authorization: Bearer ' . COHERE_API_KEY,
        'Content-Type: application/json'
    ],
    CURLOPT_POSTFIELDS     => $body,
    CURLOPT_TIMEOUT        => 25,
    CURLOPT_SSL_VERIFYPEER => true,
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlErr  = curl_error($ch);
curl_close($ch);

if ($response === false) {
    http_response_code(502);
    echo json_encode(['error' => 'Failed to connect to Cohere API: ' . $curlErr]);
    exit;
}

$data = json_decode($response, true);

if ($httpCode >= 400) {
    http_response_code(502);
    echo json_encode(['error' => 'Cohere API rejected the request', 'details' => $data]);
    exit;
}

$reply = $data['message']['content'][0]['text'] ?? 'Could not get a reply from Cohere.';

echo json_encode(['reply' => $reply], JSON_UNESCAPED_UNICODE);
