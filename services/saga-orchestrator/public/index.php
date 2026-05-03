<?php

$rootDir = dirname(__DIR__, 3);

require_once $rootDir . '/shared/Http/Response.php';
require_once $rootDir . '/shared/Http/Client.php';
require_once $rootDir . '/shared/Utils/IdGenerator.php';
require_once $rootDir . '/shared/Exceptions/SagaException.php';
require_once $rootDir . '/shared/Exceptions/ServiceException.php';
require_once $rootDir . '/shared/Database/Database.php';

require_once __DIR__ . '/../src/Saga/SagaStepStatus.php';
require_once __DIR__ . '/../src/Saga/SagaState.php';
require_once __DIR__ . '/../src/Compensation/InventoryCompensation.php';
require_once __DIR__ . '/../src/Compensation/PaymentCompensation.php';
require_once __DIR__ . '/../src/Repository/SagaRepository.php';
require_once __DIR__ . '/../src/Saga/SagaManager.php';
require_once __DIR__ . '/../src/Saga/OrderSaga.php';
require_once __DIR__ . '/../src/Controller/SagaController.php';

$storageDir = $rootDir . '/storage';
if (!is_dir($storageDir)) {
    mkdir($storageDir, 0755, true);
}
Shared\Database\Database::configure(['path' => $storageDir . '/database.sqlite']);

$orderServiceUrl = getenv('ORDER_SERVICE_URL') ?: 'http://localhost:8001';
$inventoryServiceUrl = getenv('INVENTORY_SERVICE_URL') ?: 'http://localhost:8002';
$paymentServiceUrl = getenv('PAYMENT_SERVICE_URL') ?: 'http://localhost:8003';

$controller = new SagaOrchestrator\Controller\SagaController(
    $orderServiceUrl,
    $inventoryServiceUrl,
    $paymentServiceUrl
);

$method = $_SERVER['REQUEST_METHOD'];
$uri = $_SERVER['REQUEST_URI'];

$route = null;
$data = [];

if ($method === 'POST' && $uri === '/saga/start') {
    $route = 'POST /saga/start';
    $data = json_decode(file_get_contents('php://input'), true) ?? [];
} elseif ($method === 'GET' && $uri === '/saga/status') {
    $route = 'GET /saga/status';
} elseif ($method === 'GET' && preg_match('#^/saga/([a-zA-Z0-9_-]+)$#', $uri, $matches)) {
    $route = 'GET /saga/:id';
    $data = ['sagaId' => $matches[1]];
} elseif ($method === 'POST' && $uri === '/saga/retry') {
    $route = 'POST /saga/retry';
    $data = json_decode(file_get_contents('php://input'), true) ?? [];
} elseif ($method === 'POST' && $uri === '/saga/compensate') {
    $route = 'POST /saga/compensate';
    $data = json_decode(file_get_contents('php://input'), true) ?? [];
}

$response = match ($route) {
    'POST /saga/start' => $controller->start($data),
    'GET /saga/status' => Shared\Http\Response::success(['message' => 'Saga Orchestrator is running']),
    'GET /saga/:id' => $controller->getStatus($data['sagaId']),
    'POST /saga/retry' => $controller->retry($data['sagaId'] ?? ''),
    'POST /saga/compensate' => $controller->compensate($data),
    default => Shared\Http\Response::error('Not found', 404),
};

http_response_code($response->getStatusCode());
echo $response->toJson();