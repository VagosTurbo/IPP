<?php

use IPP\Student\SemanticErrorException;

class LabelTracker
{
    /** @var array<int> */
    private array $labels = [];

    public function __construct()
    {
        $this->labels = [];
    }

    public function addLabel(mixed $label, int $order): void
    {
        $this->labels[$label] = $order;
    }

    // Returns the index of the label in the instruction list
    public function jumpIndex(mixed $label): int
    {
        if ($this->hasLabel($label)) {
            return $this->labels[$label] - 1;
        } else {
            throw new SemanticErrorException("Label " . $label . " not found.");
        }
    }

    public function hasLabel(mixed $label): bool
    {
        $label = strval($label);
        return array_key_exists($label, $this->labels);
    }

    public function printLabels(): void
    {
        echo "Labels:\n";
        foreach ($this->labels as $label => $order) {
            echo $label . " - " . $order;
        }
    }
}
