<?php

namespace IPP\Student;

use IPP\Core\Exception\IPPException;
use Throwable;

class SemanticErrorException extends IPPException
{
    public function __construct(string $message = "Semantic error occurred.", ?Throwable $previous = null)
    {
        parent::__construct($message, 52, $previous);
    }
}

class OperandTypeErrorException extends IPPException
{
    public function __construct(string $message = "Operand type error occurred.", ?Throwable $previous = null)
    {
        parent::__construct($message, 53, $previous);
    }
}

class MissingVariableException extends IPPException
{
    public function __construct(string $message = "Variable is missing.", ?Throwable $previous = null)
    {
        parent::__construct($message, 54, $previous);
    }
}

class MissingFrameException extends IPPException
{
    public function __construct(string $message = "Memory frame is missing.", ?Throwable $previous = null)
    {
        parent::__construct($message, 55, $previous);
    }
}

class MissingValueException extends IPPException
{
    public function __construct(string $message = "Value is missing.", ?Throwable $previous = null)
    {
        parent::__construct($message, 56, $previous);
    }
}

class InvalidOperandValueException extends IPPException
{
    public function __construct(string $message = "Invalid operand value.", ?Throwable $previous = null)
    {
        parent::__construct($message, 57, $previous);
    }
}

class StringErrorException extends IPPException
{
    public function __construct(string $message = "String error occurred.", ?Throwable $previous = null)
    {
        parent::__construct($message, 58, $previous);
    }
}
