# Saga E-commerce System Architecture

## Overview
This is a PHP-based microservices system implementing the Saga pattern for distributed transactions in an e-commerce order flow.

## Services

### 1. Order Service (Port 8001)
- Creates and manages orders
- Handles order lifecycle (PENDING → COMPLETED/FAILED/CANCELLED)
- Storage: JSON file-based (`services/order-service/storage/orders.json`)

### 2. Inventory Service (Port 8002)
- Manages product inventory
- Reserve/release stock for orders
- Storage: JSON file-based with seed data (`services/inventory-service/storage/inventory.json`)

### 3. Payment Service (Port 8003)
- Processes payments
- Handles refunds
- Storage: JSON file-based (`services/payment-service/storage/payments.json`)

### 4. Saga Orchestrator (Port 8000)
- Orchestrates the order saga
- Manages compensation/rollback
- Storage: JSON file-based (`services/saga-orchestrator/storage/sagas/sagas.json`)

## Request Flow

```
Client → Saga Orchestrator → Order Service
                          → Inventory Service  
                          → Payment Service
                          → Order Service (update status)
```

## Compensation (Rollback)
- On failure: releases inventory, refunds payment, cancels order

## Shared Libraries
- `shared/Http/` - HTTP client and response
- `shared/Logger/` - Logger
- `shared/Utils/` - ID Generator
- `shared/Exceptions/` - Custom exceptions
- `shared/Events/` - Event dispatcher