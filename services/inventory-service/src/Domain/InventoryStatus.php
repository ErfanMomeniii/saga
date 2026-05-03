<?php

namespace InventoryService\Domain;

enum InventoryStatus: string
{
    case AVAILABLE = 'AVAILABLE';
    case RESERVED = 'RESERVED';
    case RELEASED = 'RELEASED';
}