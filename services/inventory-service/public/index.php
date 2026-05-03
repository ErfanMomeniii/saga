<?php

$rootDir = dirname(__DIR__, 3);

require_once $rootDir . '/shared/Http/Response.php';
require_once $rootDir . '/shared/Http/Client.php';
require_once $rootDir . '/shared/Utils/IdGenerator.php';
require_once $rootDir . '/shared/Exceptions/SagaException.php';
require_once $rootDir . '/shared/Exceptions/ServiceException.php';
require_once $rootDir . '/shared/Database/Database.php';

require_once __DIR__ . '/../src/Domain/InventoryStatus.php';
require_once __DIR__ . '/../src/Domain/InventoryItem.php';
require_once __DIR__ . '/../src/DTO/ReserveInventoryRequest.php';
require_once __DIR__ . '/../src/Repository/InventoryRepository.php';
require_once __DIR__ . '/../src/Service/InventoryService.php';
require_once __DIR__ . '/../src/Controller/InventoryController.php';

$storageDir = $rootDir . '/storage';
if (!is_dir($storageDir)) {
    mkdir($storageDir, 0755, true);
}
Shared\Database\Database::configure(['path' => $storageDir . '/database.sqlite']);

$repository = new InventoryService\Repository\InventoryRepository();
$service = new InventoryService\Service\InventoryService($repository);
$controller = new InventoryService\Controller\InventoryController($service);

$method = $_SERVER['REQUEST_METHOD'];
$uri = $_SERVER['REQUEST_URI'];

$route = null;
$data = [];

if ($method === 'POST' && $uri === '/inventory/reserve') {
    $route = 'POST /inventory/reserve';
    $data = json_decode(file_get_contents('php://input'), true) ?? [];
} elseif ($method === 'POST' && $uri === '/inventory/release') {
    $route = 'POST /inventory/release';
    $data = json_decode(file_get_contents('php://input'), true) ?? [];
} elseif ($method === 'GET' && preg_match('#^/inventory/([a-zA-Z0-9_-]+)$#', $uri, $matches)) {
    $route = 'GET /inventory/:id';
    $data = ['productId' => $matches[1]];
} elseif ($method === 'GET' && $uri === '/inventory') {
    $route = 'GET /inventory';
}

$response = match ($route) {
    'POST /inventory/reserve' => $controller->reserve($data),
    'POST /inventory/release' => $controller->release($data),
    'GET /inventory/:id' => $controller->get($data['productId']),
    'GET /inventory' => $controller->getAll(),
    default => Shared\Http\Response::error('Not found', 404),
};

http_response_code($response->getStatusCode());
echo $response->toJson();