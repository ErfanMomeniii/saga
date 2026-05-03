# Saga Flow

## Normal Flow (Success Case)

```
1. POST /saga/start (order data)
   ↓
2. POST /orders (Order Service)
   - Creates order with PENDING status
   - Returns orderId
   ↓
3. POST /inventory/reserve (Inventory Service)
   - Reserves stock for order
   - Returns reserved
   ↓
4. POST /payment/process (Payment Service)
   - Processes payment
   - Returns payment + transactionId
   ↓
5. PUT /orders/status (Order Service)
   - Updates order to COMPLETED
   ↓
6. Saga COMPLETED
```

## Failure Cases

### Case A: Payment Fails
```
1. Create Order ✓
2. Reserve Inventory ✓
3. Process Payment ✗ (FAILED)
   ↓
   Rollback:
4. Release Inventory ✓
5. Cancel Order ✓
   ↓
   Saga FAILED
```

### Case B: Inventory Fails (Insufficient Stock)
```
1. Create Order ✓
2. Reserve Inventory ✗ (409 CONFLICT)
   ↓
   Rollback:
3. Cancel Order ✓
   ↓
   Saga FAILED
```

### Case C: System Crash / Retry
```
1. Saga state persisted at each step
2. On retry: resume from last failed step
3. GET /saga/:id to check state
4. POST /saga/retry to resume
```