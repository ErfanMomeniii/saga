<?php

namespace SagaOrchestrator\Saga;

enum SagaStepStatus: string
{
    case PENDING = 'PENDING';
    case IN_PROGRESS = 'IN_PROGRESS';
    case COMPLETED = 'COMPLETED';
    case FAILED = 'FAILED';
    case COMPENSATED = 'COMPENSATED';
}