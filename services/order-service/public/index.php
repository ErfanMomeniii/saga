<?php

$rootDir = dirname(__DIR__, 3);

require_once $rootDir . '/shared/Http/Response.php';
require_once $rootDir . '/shared/Http/Client.php';
require_once $rootDir . '/shared/Utils/IdGenerator.php';
require_once $rootDir . '/shared/Exceptions/SagaException.php';
require_once $rootDir . '/shared/Exceptions/ServiceException.php';
require_once $rootDir . '/shared/Database/Database.php';

require_once __DIR__ . '/../src/Domain/OrderStatus.php';
require_once __DIR__ . '/../src/Domain/Order.php';
require_once __DIR__ . '/../src/DTO/CreateOrderRequest.php';
require_once __DIR__ . '/../src/Repository/OrderRepository.php';
require_once __DIR__ . '/../src/Service/OrderService.php';
require_once __DIR__ . '/../src/Controller/OrderController.php';

$storageDir = $rootDir . '/storage';
if (!is_dir($storageDir)) {
    mkdir($storageDir, 0755, true);
}
Shared\Database\Database::configure(['path' => $storageDir . '/database.sqlite']);

$repository = new OrderService\Repository\OrderRepository();
$service = new OrderService\Service\OrderService($repository);
$controller = new OrderService\Controller\OrderController($service);

$method = $_SERVER['REQUEST_METHOD'];
$uri = $_SERVER['REQUEST_URI'];

$route = null;
$data = [];

if ($method === 'POST' && $uri === '/orders') {
    $route = 'POST /orders';
    $data = json_decode(file_get_contents('php://input'), true) ?? [];
} elseif ($method === 'GET' && preg_match("#^/orders/([a-zA-Z0-9_-]+)$#", $uri, $matches)) {
    $route = 'GET /orders/:id';
    $data = ['orderId' => $matches[1]];
} elseif ($method === 'PUT' && $uri === '/orders/status') {
    $route = 'PUT /orders/status';
    $data = json_decode(file_get_contents('php://input'), true) ?? [];
}

$response = match ($route) {
    'POST /orders' => $controller->create($data),
    'GET /orders/:id' => $controller->get($data['orderId']),
    'PUT /orders/status' => $controller->updateStatus($data),
    default => Shared\Http\Response::error('Not found', 404),
};

http_response_code($response->getStatusCode());
echo $response->toJson();