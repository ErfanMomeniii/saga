# API Contracts

## Order Service (port 8001)

### Create Order
```
POST /orders
{
  "customerId": "CUST-001",
  "items": [
    {"productId": "PROD-001", "quantity": 2, "price": 999.99},
    {"productId": "PROD-002", "quantity": 1, "price": 499.99}
  ]
}

Response (201):
{
  "id": "ORD_xxx",
  "customerId": "CUST-001",
  "items": [...],
  "totalAmount": 2499.97,
  "status": "PENDING",
  "createdAt": "2025-01-01T00:00:00+00:00",
  ...
}
```

### Get Order
```
GET /orders/:orderId

Response (200):
{ "order": ... }

Response (404):
{ "error": "Order not found" }
```

### Update Order Status
```
PUT /orders/status
{
  "orderId": "ORD_xxx",
  "status": "COMPLETED|CANCELLED|FAILED"
}
```

## Inventory Service (port 8002)

### Reserve Inventory
```
POST /inventory/reserve
{
  "orderId": "ORD_xxx",
  "items": [
    {"productId": "PROD-001", "quantity": 2},
    {"productId": "PROD-002", "quantity": 1}
  ]
}

Response (200):
{
  "orderId": "ORD_xxx",
  "reserved": ["PROD-001", "PROD-002"]
}

Response (409):
{ "error": "Insufficient inventory for: PROD-001" }
```

### Release Inventory
```
POST /inventory/release
{
  "orderId": "ORD_xxx"
}
```

### Get Inventory
```
GET /inventory/:productId
GET /inventory
```

## Payment Service (port 8003)

### Process Payment
```
POST /payment/process
{
  "orderId": "ORD_xxx",
  "customerId": "CUST-001",
  "amount": 2499.97
}

Response (201):
{
  "id": "PAY_xxx",
  "orderId": "ORD_xxx",
  "amount": 2499.97,
  "status": "COMPLETED",
  "transactionId": "TXN_xxx"
}
```

### Refund Payment
```
POST /payment/refund
{
  "orderId": "ORD_xxx"
}

Response (200):
{
  "message": "Payment refunded",
  "payment": {...}
}
```

## Saga Orchestrator (port 8000)

### Start Saga
```
POST /saga/start
{
  "customerId": "CUST-001",
  "items": [...],
  "totalAmount": 2499.97
}

Response (200):
{
  "success": true,
  "sagaId": "SAGA_xxx",
  "data": {
    "orderId": "ORD_xxx"
  }
}
```

### Get Saga Status
```
GET /saga/:sagaId

Response (200):
{
  "id": "SAGA_xxx",
  "type": "ORDER_SAGA",
  "status": "COMPLETED|FAILED|IN_PROGRESS",
  "steps": [
    {"name": "create_order", "status": "COMPLETED"},
    {"name": "reserve_inventory", "status": "COMPLETED"},
    {"name": "process_payment", "status": "COMPLETED"},
    {"name": "complete_order", "status": "COMPLETED"}
  ],
  "currentStep": 4,
  ...
}
```

### Retry Saga
```
POST /saga/retry
{
  "sagaId": "SAGA_xxx"
}
```

### Compensate (Manual Rollback)
```
POST /saga/compensate
{
  "sagaId": "SAGA_xxx"
}
```