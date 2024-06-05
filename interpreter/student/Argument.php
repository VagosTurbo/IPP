<?php
require_once 'Exception.php';

use IPP\Student\OperandTypeErrorException;

class Argument
{
    private string $type;
    private mixed $value;

    public function __construct(string $argtype, mixed $argvalue)
    {
        if (!in_array($argtype, ['int', 'bool', 'string', 'nil', 'label', 'type', 'var'])) {
            throw new OperandTypeErrorException();
        }

        //strip argvalue from whitespaces
        $argvalue = preg_replace('/\s+/', '', $argvalue);

        $this->type = $argtype;
        switch ($argtype) {
            case 'int':
                $this->value = (int)$argvalue;
                break;
            case 'bool':
                $this->value = $argvalue === 'true' ? true : false;
                break;
            case 'string':
                $this->value = $argvalue;
                break;
            case 'nil':
                $this->value = null;
                break;
            case 'label':
                $this->value = $argvalue;
                break;
            case 'type':
                $this->value = $argvalue;
                break;
            case 'var':
                $this->value = $argvalue;
                break;
            default:
                // throw new OperandTypeErrorException();
                break;
        }
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getValue(): mixed
    {
        return $this->value;
    }
}
