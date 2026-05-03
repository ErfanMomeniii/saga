<?php

$rootDir = dirname(__DIR__, 3);

require_once $rootDir . '/shared/Http/Response.php';
require_once $rootDir . '/shared/Http/Client.php';
require_once $rootDir . '/shared/Utils/IdGenerator.php';
require_once $rootDir . '/shared/Exceptions/SagaException.php';
require_once $rootDir . '/shared/Exceptions/ServiceException.php';
require_once $rootDir . '/shared/Database/Database.php';

require_once __DIR__ . '/../src/Domain/PaymentStatus.php';
require_once __DIR__ . '/../src/Domain/Payment.php';
require_once __DIR__ . '/../src/DTO/CreatePaymentRequest.php';
require_once __DIR__ . '/../src/Repository/PaymentRepository.php';
require_once __DIR__ . '/../src/Service/PaymentService.php';
require_once __DIR__ . '/../src/Controller/PaymentController.php';

$storageDir = $rootDir . '/storage';
if (!is_dir($storageDir)) {
    mkdir($storageDir, 0755, true);
}
Shared\Database\Database::configure(['path' => $storageDir . '/database.sqlite']);

$repository = new PaymentService\Repository\PaymentRepository();
$service = new PaymentService\Service\PaymentService($repository);
$controller = new PaymentService\Controller\PaymentController($service);

$method = $_SERVER['REQUEST_METHOD'];
$uri = $_SERVER['REQUEST_URI'];

$route = null;
$data = [];

if ($method === 'POST' && $uri === '/payment/process') {
    $route = 'POST /payment/process';
    $data = json_decode(file_get_contents('php://input'), true) ?? [];
} elseif ($method === 'POST' && $uri === '/payment/refund') {
    $route = 'POST /payment/refund';
    $data = json_decode(file_get_contents('php://input'), true) ?? [];
} elseif ($method === 'GET' && preg_match('#^/payment/([a-zA-Z0-9_-]+)$#', $uri, $matches)) {
    $route = 'GET /payment/:id';
    $data = ['orderId' => $matches[1]];
}

$response = match ($route) {
    'POST /payment/process' => $controller->process($data),
    'POST /payment/refund' => $controller->refund($data),
    'GET /payment/:id' => $controller->get($data['orderId']),
    default => Shared\Http\Response::error('Not found', 404),
};

http_response_code($response->getStatusCode());
echo $response->toJson();