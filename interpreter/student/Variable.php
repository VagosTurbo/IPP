<?php

// Variable.php

class Variable
{
    private string $name;
    private mixed $value;
    private mixed $type;

    public function __construct(string $name, mixed $value, mixed $type)
    {
        $this->name = $name;
        $this->value = $value;
        $this->type = $type;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getValue(): mixed
    {
        return $this->value;
    }

    public function setValue(mixed $value): void
    {
        $this->value = $value;
    }

    public function getType(): mixed
    {
        return $this->type;
    }

    public function setType(mixed $type): void
    {
        $this->type = $type;
    }

    public function __toString(): string
    {
        return $this->name . " " . $this->value . " " . $this->type;
    }
}
