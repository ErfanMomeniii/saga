<?php

namespace Shared\Exceptions;

use Exception;

class ServiceException extends Exception
{
    public function __construct(string $message, array $context = [])
    {
        $fullMessage = $context ? $message . ' - Context: ' . json_encode($context) : $message;
        parent::__construct($fullMessage);
    }
}