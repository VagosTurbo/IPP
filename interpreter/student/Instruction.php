<?php


require_once 'Argument.php';

// Instruction.php
class Instruction
{
    private string $opcode;
    /** @var Argument[] */
    private array $args;
    private int $order;

    /**
     * @param string $opcode
     * @param Argument[] $args
     * @param int $order
     */
    public function __construct(string $opcode, array $args, int $order)
    {
        $this->opcode = $opcode;
        $this->order = $order;
        $this->args = $args;
    }

    /**
     * @return string
     */
    public function getOpcode(): string
    {
        return $this->opcode;
    }

    /**
     * @return Argument[]
     */
    public function getArguments(): array
    {
        return $this->args;
    }

    /**
     * @return int
     */
    public function getOrder(): int
    {
        return $this->order;
    }
}
