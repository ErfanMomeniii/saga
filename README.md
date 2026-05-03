# Saga E-commerce System

A pure PHP microservices implementation of the Saga pattern for distributed transactions in an e-commerce order flow.

## Overview

This project demonstrates the Saga pattern - a sequence of local transactions where each step updates the database and publishes an event. If one step fails, compensating transactions undo the previous steps.

## What is the Saga Pattern?

The Saga pattern is a distributed transaction pattern that manages operations spanning multiple services in a microservices architecture. Unlike traditional ACID transactions that guarantee atomicity through locks, Saga achieves consistency through a sequence of coordinated local transactions with compensation.

### When to Use Saga

- **Distributed Systems**: When operations span multiple databases or services
- **Eventual Consistency**: When immediate consistency is not required
- **Long-Running Processes**: Business processes that take minutes or hours
- **Service Decomposition**: When monolithic databases are split into microservices

### Saga vs Traditional Transactions

| Aspect | ACID Transactions | Saga Pattern |
|--------|-------------------|-------------|
| Consistency | Immediate (strong) | Eventual |
| Latency | High (locks held) | Lower (no locks) |
| Scalability | Limited | Highly scalable |
| Failure Recovery | Rollback | Compensation |
| Complexity | Low | Higher |

## Saga Types

### 1. Choreography-Based Saga

In choreography, services communicate by exchanging events directly. Each service listens for events and decides what action to take.

```
     ┌──────────────┐      OrderCreated       ┌──────────────────┐
     │ Order        │ ──────────────────────▶ │ Inventory        │
     │ Service      │                         │ Service          │
     └──────────────┘                         └──────────────────┘
           ▲                                       │
           │            InventoryReserved          │
           └───────────────────────────────────────┘
           ▲                                       │
           │            PaymentReserved            │
           └───────────────────────────────────────┘
           ▲                                       │
           │            PaymentCompleted           │
           └───────────────────────────────────────┘
```

**Pros:**
- Simple, no central coordinator
- Services are loosely coupled
- Good for small systems

**Cons:**
- Hard to track overall progress
- Cyclic dependencies possible
- Difficult to debug

### 2. Orchestration-Based Saga

In orchestration, a central coordinator (orchestrator) tells participants what to do and handles failure recovery.

```
                    ┌───────────────────┐
                    │       Saga        │
                    │    Orchestrator   │
                    └─────────┬─────────┘
                              │
              ┌───────────────┼───────────────┐
              │               │               │
              ▼               ▼               ▼
        ┌──────────┐   ┌────────────┐   ┌──────────┐
        │ Order    │   │ Inventory  │   │ Payment  │
        │ Service  │   │ Service    │   │ Service  │
        └──────────┘   └────────────┘   └──────────┘
              │               │               │
              └───────────────┴───────────────┘
                        Compensation
```

**Pros:**
- Clear visibility of process state
- Easier error handling
- Better for complex workflows
- Easier to test

**Cons:**
- Central point of failure
- More coupling to orchestrator

### This Project Uses Orchestration

This project implements **orchestration-based Saga** via the `saga-orchestrator` service, which coordinates the order flow across services.

## Architecture

```
                      ┌──────────────────────┐
                      │  Saga Orchestrator   │
                      │     (Port 8000)      │
                      └──────────┬───────────┘
                                 │
            ┌────────────────────┼───────────────────┐
            │                    │                   │
            ▼                    ▼                   ▼
   ┌─────────────────┐ ┌──────────────────┐ ┌─────────────────┐
   │   Order Service │ │Inventory Service │ │ Payment Service │
   │    (Port 8001)  │ │   (Port 8002)    │ │   (Port 8003)   │
   └─────────────────┘ └──────────────────┘ └─────────────────┘
```

### Services

| Service | Port | Responsibility |
|---------|------|----------------|
| Saga Orchestrator | 8000 | Coordinates the order flow, handles compensation |
| Order Service | 8001 | Creates and manages orders |
| Inventory Service | 8002 | Reserves/releases inventory |
| Payment Service | 8003 | Processes/refunds payments |

## Saga Flow

### Success Flow

```
START
  │
  ▼
[Create Order] ──▶ PENDING
  │
  ▼
[Reserve Inventory] ──▶ RESERVED
  │
  ▼
[Process Payment] ──▶ COMPLETED
  │
  ▼
[Update Order] ──▶ COMPLETED
  │
  ▼
SAGA COMPLETED ✓
```

### Failure: Payment Fails

```
[Create Order] ──▶ PENDING ✓
[Reserve Inventory] ──▶ RESERVED ✓
[Process Payment] ──▶ FAILED ✗
        │
        ▼ Compensation
[Release Inventory] ◀── Undo
[Cancel Order] ◀─────── Undo
        │
        ▼
SAGA FAILED
```

### Failure: Inventory Unavailable

```
[Create Order] ──▶ PENDING ✓
[Reserve Inventory] ──▶ FAILED (no stock) ✗
        │
        ▼ Compensation
[Cancel Order] ◀─────── Undo
        │
        ▼
SAGA FAILED
```

## Compensation

Compensation is the key to Saga's reliability. Each action has a corresponding "undo" action:

| Action | Compensation |
|--------|--------------|
| Create Order | Cancel Order |
| Reserve Inventory | Release Inventory |
| Process Payment | Refund Payment |

## Quick Start

### Using PHP Built-in Server

```bash
# Install dependencies for each service
cd services/order-service && composer install
cd services/inventory-service && composer install
cd services/payment-service && composer install
cd services/saga-orchestrator && composer install

# Start Order Service
php -S localhost:8001 -t public services/order-service/public/index.php &

# Start Inventory Service
php -S localhost:8002 -t public services/inventory-service/public/index.php &

# Start Payment Service
php -S localhost:8003 -t public services/payment-service/public/index.php &

# Start Saga Orchestrator
php -S localhost:8000 -t public services/saga-orchestrator/public/index.php &
```

### Using Docker

```bash
cd infra/docker
docker-compose up --build
```

### Using Start Script

```bash
bash infra/scripts/start-all.sh
```

## API Examples

### Create Order via Saga

```bash
curl -X POST http://localhost:8000/saga/start \
  -H "Content-Type: application/json" \
  -d '{
    "customerId": "CUST-001",
    "items": [
      {"productId": "PROD-001", "quantity": 1, "price": 999.99},
      {"productId": "PROD-002", "quantity": 2, "price": 49.99}
    ],
    "totalAmount": 1099.97
  }'
```

### Response

```json
{
  "sagaId": "SAGA_abc123",
  "status": "RUNNING",
  "orderId": "ORD_def456",
  "steps": [
    {"service": "order", "action": "create", "status": "COMPLETED"},
    {"service": "inventory", "action": "reserve", "status": "PENDING"},
    {"service": "payment", "action": "process", "status": "PENDING"},
    {"service": "order", "action": "update", "status": "PENDING"}
  ]
}
```

### Check Order Status

```bash
curl http://localhost:8001/orders/ORD_def456
```

### Check Saga Status

```bash
curl http://localhost:8000/saga/SAGA_abc123
```

### Retry Failed Saga

```bash
curl -X POST http://localhost:8000/saga/retry \
  -H "Content-Type: application/json" \
  -d '{"sagaId": "SAGA_abc123"}'
```

## Project Structure

```
saga/
├── services/
│   ├── order-service/           # Order management
│   │   ├── src/
│   │   │   ├── Controller/
│   │   │   ├── Service/
│   │   │   ├── Repository/
│   │   │   ├── Domain/
│   │   │   └── DTO/
│   │   ├── public/
│   │   └── composer.json
│   │
│   ├── inventory-service/      # Stock management
│   │   ├── src/
│   │   ├── public/
│   │   └── composer.json
│   │
│   ├── payment-service/         # Payment processing
│   │   ├── src/
│   │   ├── public/
│   │   └── composer.json
│   │
│   └── saga-orchestrator/        # Saga coordination
│       ├── src/
│       │   ├── Controller/
│       │   ├── Saga/
│       │   ├── Compensation/
│       │   └── Config/
│       ├── public/
│       └── composer.json
│
├── shared/                      # Shared libraries
│   ├── Http/                    # HTTP client & response
│   ├── Exceptions/             # Custom exceptions
│   └── Utils/                    # Utilities
│
├── infra/
│   ├── docker/                  # Docker config
│   │   ├── docker-compose.yml
│   │   ├── mysql/
│   │   └── nginx/
│   ├── postman/                 # Postman tests
│   └── scripts/                 # Startup scripts
│
├── docs/                        # Documentation
│   ├── architecture.md
│   ├── api-contracts.md
│   └── saga-flow.md
│
└── storage/                     # SQLite databases
```

## Testing

### Test Successful Order

```bash
curl -X POST http://localhost:8000/saga/start \
  -H "Content-Type: application/json" \
  -d '{
    "customerId": "CUST-001",
    "items": [{"productId": "PROD-001", "quantity": 1, "price": 100}],
    "totalAmount": 100
  }'
```

### Check Inventory After (should be decreased)

```bash
curl http://localhost:8002/inventory/PROD-001
```

### Test Compensation (Invalid Quantity)

```bash
curl -X POST http://localhost:8002/inventory/reserve \
  -H "Content-Type: application/json" \
  -d '{
    "orderId": "TEST999",
    "items": [{"productId": "PROD-001", "quantity": 9999}]
  }'
```

## Saga State Machine

```
    ┌──────────┐
    │  START   │
    └────┬─────┘
         │ create order
         ▼
    ┌────────────┐
    │  RUNNING   │────────── reserve inventory
    └────┬───────┘                        │
         │                                ▼
         │                    ┌────────────────────┐
         │                    │ INVENTORY_RESERVED │
         └────────────────────┴────────────────────┘
                              │
                              ▼ process payment
                    ┌─────────────────┐
                    │ PAYMENT_COMPLETE│─────────── update order
                    └────────┬────────┘                    │
                             │                             ▼
                             │                   ┌──────────────┐
                             │                   │  COMPLETED   │
                             └───────────────────┴──────────────┘

    COMPENSATING ◀───────────│
         │                   │
         ▼                   ▼ release inventory
┌───────────────────┐    ┌──────────────┐
│ INVENTORY_RELEASED│    │ CANCEL_ORDER │
└────────┬──────────┘    └──────────────┘
         │                      │
         ▼                      ▼ refund payment
┌─────────────────┐      ┌────────────────┐
│PAYMENT_REFUNDED │      │   COMPENSATED  │
└─────────────────┘      └────────────────┘
         │
         ▼
┌─────────────────┐
│     FAILED      │
└─────────────────┘
```

## Key Concepts

### 1. Idempotency

Operations should be idempotent - calling them multiple times should produce the same result. Use unique order IDs to prevent duplicate charges.

### 2. Compensable Transactions

Each step must have a compensate action. Design compensation before implementing the forward action.

### 3. Saga Log

Store saga state to track progress and enable recovery after crashes.

### 4. Timeout

Set timeouts for each step. If a step takes too long, trigger compensation.

### 5. Partial Completion

Handle cases where some compensations succeed but others fail.

## Best Practices

1. **Keep-saga short** - Long sagas are hard to manage
2. **Design compensation first** - Plan rollback before forward action
3. **Use unique IDs** - Prevent duplicate operations
4. **Log everything** - Enable debugging and recovery
5. **Handle timeouts** - Don't wait forever for a response
6. **Test failure paths** - Verify compensation works correctly

## Technologies

- Pure PHP 8.1+
- No framework dependencies
- JSON/SQLite file-based storage
- RESTful HTTP communication
- Saga pattern with compensation

## References

- [Saga Pattern - Microsoft](https://learn.microsoft.com/en-us/azure/architecture/reference-architectures/saga/saga-pattern)
- [Distributed Transactions - Crane](https://www.infoq.com/articles/saga-pattern-on-microservices/)
- [Choreography vs Orchestration](https://serverless360.com/blog/choreography-vs-orchestration-in-microservices/)

## License

MIT