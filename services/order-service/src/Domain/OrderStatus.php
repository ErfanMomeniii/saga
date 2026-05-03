<?php

namespace OrderService\Domain;

enum OrderStatus: string
{
    case PENDING = 'PENDING';
    case RESERVING_INVENTORY = 'RESERVING_INVENTORY';
    case PROCESSING_PAYMENT = 'PROCESSING_PAYMENT';
    case COMPLETED = 'COMPLETED';
    case FAILED = 'FAILED';
    case CANCELLED = 'CANCELLED';
}