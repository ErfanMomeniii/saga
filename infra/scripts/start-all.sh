#!/bin/bash

echo "Starting Order Service on port 8001..."
cd services/order-service && php -S 0.0.0.0:8001 -t public public/index.php > /tmp/order-service.log 2>&1 &

echo "Starting Inventory Service on port 8002..."
cd services/inventory-service && php -S 0.0.0.0:8002 -t public public/index.php > /tmp/inventory-service.log 2>&1 &

echo "Starting Payment Service on port 8003..."
cd services/payment-service && php -S 0.0.0.0:8003 -t public public/index.php > /tmp/payment-service.log 2>&1 &

echo "Starting Saga Orchestrator on port 8000..."
cd services/saga-orchestrator && php -S 0.0.0.0:8000 -t public public/index.php > /tmp/saga-orchestrator.log 2>&1 &

echo "All services started!"
echo "Order Service: http://localhost:8001"
echo "Inventory Service: http://localhost:8002"
echo "Payment Service: http://localhost:8003"
echo "Saga Orchestrator: http://localhost:8000"