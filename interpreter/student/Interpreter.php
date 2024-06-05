<?php

namespace IPP\Student;

use Instruction;
use IPP\Core\AbstractInterpreter;
use MemoryManager;
use Argument;
use IPP\Core\FileSourceReader;
use LabelTracker;

require_once 'MemoryManager.php';
require_once 'Instruction.php';
require_once 'LabelTracker.php';


class Interpreter extends AbstractInterpreter
{
    /** @var Instruction[]
     */
    private array $instructions;
    private MemoryManager $memoryManager;
    private LabelTracker $labelTracker;
    /** @var array<mixed> */
    private array $dataStack;
    /** @var array<int> */
    private array $callStack;


    public function execute(): int
    {
        // Initialize memory manager, label tracker, data stack and call stack
        $this->memoryManager = new MemoryManager();
        $this->labelTracker = new LabelTracker();
        $this->dataStack = [];
        $this->callStack = [];

        // Get the DOM document from the source
        $dom = $this->source->getDOMDocument();

        // check xml structure
        $a = $dom->getElementsByTagName('program');
        if ($a->item(0)->getAttribute('language') !== 'IPPcode24' || $a->length !== 1) {
            $this->stderr->writeString("Error: Invalid XML structure\n");
            exit(32);
        }


        // Extract instructions from the XML
        $this->instructions = [];
        $rawInstructions = $dom->getElementsByTagName('instruction');
        $orderCheck = [];
        foreach ($rawInstructions as $instruction) {
            $opcode = $instruction->getAttribute('opcode');
            $order = $instruction->getAttribute('order');
            $order = intval($order);

            if (in_array($order, $orderCheck) || $order < 1) {
                $this->stderr->writeString("Error: Order of instructions\n");
                exit(32);
            }
            $orderCheck[] = $order;

            $args = [];
            $arg1 = $instruction->getElementsByTagName('arg1')->item(0);
            $arg2 = $instruction->getElementsByTagName('arg2')->item(0);
            $arg3 = $instruction->getElementsByTagName('arg3')->item(0);

            if ($arg1 !== null) {
                $argType = $arg1->getAttribute('type');
                if ($argType == null) {
                    $this->stderr->writeString("Error: Missing argument type\n");
                    exit(32);
                }
                $argValue = (string)$arg1->nodeValue;
                $args[] = new Argument($argType, $argValue);
            }

            if ($arg2 !== null) {
                $argType = $arg2->getAttribute('type');
                if ($argType == null) {
                    $this->stderr->writeString("Error: Missing argument type\n");
                    exit(32);
                }
                $argValue = (string)$arg2->nodeValue;
                $args[] = new Argument($argType, $argValue);
            }

            if ($arg3 !== null) {
                $argType = $arg3->getAttribute('type');
                if ($argType == null) {
                    $this->stderr->writeString("Error: Missing argument type\n");
                    exit(32);
                }
                $argValue = (string)$arg3->nodeValue;
                $args[] = new Argument($argType, $argValue);
            }

            // Create an Instruction object and add it to the $instructions array
            $this->instructions[] = new Instruction($opcode, $args, $order);
        }

        // Sort instructions by order
        usort($this->instructions, function ($a, $b) {
            return $a->getOrder() - $b->getOrder();
        });

        // Iterate through the sorted instructions and get labels indexes
        for ($i = 0; $i < count($this->instructions); $i++) {
            $instruction = $this->instructions[$i];
            $opcode = $instruction->getOpcode();
            $args = $instruction->getArguments();
            if ($opcode === 'LABEL') {
                $this->labelTracker->addLabel($args[0]->getValue(), $i + 1);
            }
        }

        // Iterate through the sorted instructions and execute them
        for ($i = 0; $i < count($this->instructions); $i++) {
            $instruction = $this->instructions[$i];
            $opcode = $instruction->getOpcode();
            $args = $instruction->getArguments();
            $opcode = strtoupper($opcode);
            // print_r($instruction->getOrder() . " " . $opcode . "\n");
            switch ($opcode) {
                case 'DEFVAR':
                    $this->checkSyntax($args, 1);
                    $this->memoryManager->defvar($args[0]);
                    break;
                case 'MOVE':
                    $this->checkSyntax($args, 2);
                    $this->memoryManager->move($args[0], $args[1]);
                    break;
                case 'CREATEFRAME':
                    $this->checkSyntax($args, 0);
                    $this->memoryManager->createFrame();
                    break;
                case 'PUSHFRAME':
                    $this->checkSyntax($args, 0);
                    $this->memoryManager->pushFrame();
                    break;
                case 'POPFRAME':
                    $this->checkSyntax($args, 0);
                    $this->memoryManager->popFrame();
                    break;
                case 'LABEL':
                    $this->checkSyntax($args, 1);
                    $this->labelTracker->addLabel($args[0]->getValue(), $i + 1);
                    break;
                case 'JUMP':
                    $this->checkSyntax($args, 1);
                    $i = $this->labelTracker->jumpIndex($args[0]->getValue());
                    break;
                case 'JUMPIFEQ':
                    $this->checkSyntax($args, 3);
                    if ($this->labelTracker->hasLabel($args[0]->getValue()) === false) {
                    }

                    if ($this->memoryManager->jumpifeq($args[1], $args[2]) === true) {
                        $i = $this->labelTracker->jumpIndex($args[0]->getValue());
                    } else {
                    }
                    break;
                case 'JUMPIFNEQ':
                    $this->checkSyntax($args, 3);
                    if ($this->memoryManager->jumpifneq($args[1], $args[2])) {
                        $i = $this->labelTracker->jumpIndex($args[0]->getValue());
                    }
                    break;
                case 'WRITE':
                    $this->checkSyntax($args, 1);
                    // Get the type and value of the argument
                    $type = $this->memoryManager->getSymbType($args[0]);
                    $val = $this->memoryManager->getSymbValue($args[0]);
                    // Print the value to the stdout based on the type
                    switch ($type) {
                        case 'int':
                            $this->stdout->writeInt($val);
                            break;
                        case 'bool':
                            $this->stdout->writeBool($val);
                            break;
                        case 'string':
                            $this->stdout->writeString($this->convertToAscii($val));
                            break;
                        case 'nil':
                            $this->stdout->writeString("");
                            break;
                        default:
                            throw new MissingValueException();
                    }
                    break;
                case 'ADD':
                    $this->checkSyntax($args, 3);
                    $this->memoryManager->addSymbols($args[0], $args[1], $args[2]);
                    break;
                case 'SUB':
                    $this->checkSyntax($args, 3);
                    $this->memoryManager->subSymbols($args[0], $args[1], $args[2]);
                    break;
                case 'MUL':
                    $this->checkSyntax($args, 3);
                    $this->memoryManager->mulSymbols($args[0], $args[1], $args[2]);
                    break;
                case 'IDIV':
                    $this->checkSyntax($args, 3);
                    $this->memoryManager->idivSymbols($args[0], $args[1], $args[2]);
                    break;
                case 'PUSHS':
                    $this->checkSyntax($args, 1);
                    // Transform Variable to Argument
                    if ($args[0]->getType() === 'var') {
                        $val = $this->memoryManager->getSymbValue($args[0]);
                        $push_type = $this->memoryManager->getSymbType($args[0]);
                    } else {
                        $val = $args[0]->getValue();
                        $push_type = $args[0]->getType();
                    }
                    array_push($this->dataStack, new Argument($push_type, $val));
                    break;
                case 'POPS':
                    $this->checkSyntax($args, 1);
                    // If the stack is empty, throw an exception
                    if ($this->dataStack === []) {
                        throw new MissingValueException();
                    }

                    $this->memoryManager->move($args[0], array_pop($this->dataStack));
                    break;
                case 'INT2CHAR':
                    $this->checkSyntax($args, 2);
                    $this->memoryManager->int2char($args[0], $args[1]);
                    break;
                case 'CONCAT':
                    $this->checkSyntax($args, 3);
                    $this->concat($args[0], $args[1], $args[2]);
                    break;
                case 'GETCHAR':
                    $this->checkSyntax($args, 3);
                    $this->memoryManager->getChar($args[0], $args[1], $args[2]);
                    break;
                case 'SETCHAR':
                    $this->checkSyntax($args, 3);
                    $this->memoryManager->setChar($args[0], $args[1], $args[2]);
                    break;
                case 'STRLEN':
                    $this->checkSyntax($args, 2);
                    $this->strLen($args[0], $args[1]);
                    break;
                case 'NOT':
                    $this->checkSyntax($args, 2);
                    $this->memoryManager->not($args[0], $args[1]);
                    break;
                case 'EXIT':
                    $this->checkSyntax($args, 1);
                    $this->memoryManager->exit_command($args[0]);
                    break;
                case 'CALL':
                    $this->checkSyntax($args, 1);
                    // Push the next instruction index to the call stack, jump to the label
                    if ($this->labelTracker->hasLabel($args[0]->getValue()) === true) {
                        array_push($this->callStack, $i);
                        $i = $this->labelTracker->jumpIndex($args[0]->getValue());
                    } else {
                        throw new SemanticErrorException("Label " . $args[0]->getValue() . " not found.");
                    }
                    break;
                case 'RETURN':
                    $this->checkSyntax($args, 0);
                    // If return was called without a call, throw an exception
                    if (empty($this->callStack)) {
                        throw new MissingValueException();
                    }
                    $i = array_pop($this->callStack);
                    break;
                case 'OR':
                    $this->checkSyntax($args, 3);
                    $this->memoryManager->or($args[0], $args[1], $args[2]);
                    break;
                case 'AND':
                    $this->checkSyntax($args, 3);
                    $this->memoryManager->and($args[0], $args[1], $args[2]);
                    break;
                case 'STRI2INT':
                    $this->checkSyntax($args, 3);
                    $this->memoryManager->stritoint($args[0], $args[1], $args[2]);
                    break;
                case 'TYPE':
                    $this->checkSyntax($args, 2);
                    $this->memoryManager->type($args[0], $args[1]);
                    break;
                case 'READ':
                    $this->checkSyntax($args, 2);
                    $this->read($args[0], $args[1]);
                    break;
                case 'LT':
                    $this->checkSyntax($args, 3);
                    $this->memoryManager->lessthan($args[0], $args[1], $args[2]);
                    break;
                case 'GT':
                    $this->checkSyntax($args, 3);
                    $this->memoryManager->greatthan($args[0], $args[1], $args[2]);
                    break;
                case 'EQ':
                    $this->checkSyntax($args, 3);
                    $this->memoryManager->equal($args[0], $args[1], $args[2]);
                    break;
                case 'DPRINT':
                    $this->checkSyntax($args, 1);
                    $type = $this->memoryManager->getSymbType($args[0]);
                    $val = $this->memoryManager->getSymbValue($args[0]);
                    switch ($type) {
                        case 'int':
                            $this->stdout->writeInt($val);
                            break;
                        case 'bool':
                            $this->stdout->writeBool($val);
                            break;
                        case 'string':
                            $this->stdout->writeString($this->convertToAscii($val));
                            break;

                        default:
                            throw new MissingValueException();
                    }
                    break;
                case 'BREAK':
                    $this->checkSyntax($args, 0);
                    $this->memoryManager->printAllFrames();
                    $this->labelTracker->printLabels();
                    $this->printInstructions();
                    break;

                default:
                    echo "Unknown opcode: " . $opcode . "\n";
                    break;
            }
        }

        // $this->memoryManager->printAllFrames();

        return 0;
    }

    private function printInstructions(): void
    {
        foreach ($this->instructions as $instruction) {
            $this->stderr->writeString($instruction->getOpcode() . " " . $instruction->getOrder() . " - ");
            foreach ($instruction->getArguments() as $arg) {
                $this->stderr->writeString($arg->getType() . " " . $arg->getValue() . " ");
            }
            $this->stderr->writeString("\n");
        }
    }

    /**
     * Check the number of arguments in the instruction
     * @param array<Argument> $a
     * @param int $count
     */
    private function checkSyntax(array $a, int $count): void
    {
        if (count($a) !== $count) {
            $this->stderr->writeString("Error: Invalid number of arguments\n");
            exit(32);
        }
    }

    /**
     * Converts \xyz sequences to ASCII characters
     * @param string $string
     * @return string
     */
    private function convertToAscii(string $string): string
    {
        // Regex pattern to match \xyz sequences
        $pattern = '/\\\\([0-9]{3})/';

        // Replace \xyz sequences with their corresponding ASCII characters
        $result = preg_replace_callback($pattern, function ($matches) {
            // Convert the matched \xyz sequence to ASCII character
            $ascii_char = chr(intval($matches[1]));
            return $ascii_char;
        }, $string);

        return $result;
    }

    /**
     * CONCAT instruction
     * @param Argument $arg1
     * @param Argument $arg2
     * @param Argument $arg3
     * @return void
     * @throws OperandTypeErrorException
     */
    private function concat(Argument $arg1, Argument $arg2, Argument $arg3): void
    {
        if ($this->memoryManager->getSymbType($arg2) !== 'string' || $this->memoryManager->getSymbType($arg3) !== 'string') {
            throw new OperandTypeErrorException();
        }

        $var1 = $this->memoryManager->getSymbValue($arg2);
        $var2 = $this->memoryManager->getSymbValue($arg3);

        $result = $var1 . $var2;

        $this->memoryManager->move($arg1, new Argument("string", $result));
    }

    /**
     * STRLEN instruction
     * @param Argument $arg1
     * @param Argument $arg2
     * @return void
     * @throws OperandTypeErrorException
     */
    private function strLen(Argument $arg1, Argument $arg2): void
    {
        if ($this->memoryManager->getSymbType($arg2) !== 'string') {
            throw new OperandTypeErrorException();
        }

        $var1 = $this->memoryManager->getSymbValue($arg2);

        $result = strlen((string)$var1);

        $this->memoryManager->move($arg1, new Argument("int", (int)$result));
    }

    /**
     * READ instruction
     * @param Argument $arg1
     * @param Argument $arg2
     * @return void
     */
    private function read(Argument $arg1, Argument $arg2): void
    {
        $type = $this->memoryManager->getSymbValue($arg2);
        if ($type !== 'int' && $type !== 'bool' && $type !== 'string') {
            exit(32);
        }
        $val = null;
        switch ($type) {
            case 'int':
                $val = $this->input->readInt();
                break;
            case 'bool':
                $val = $this->input->readBool();
                if ($val == 1) {
                    $val = 'true';
                } else {
                    $val = 'false';
                }
                break;
            case 'string':
                $val = $this->input->readString();
                break;
            default:
                break;
        }

        $this->memoryManager->move($arg1, new Argument($type, $val));
    }
}
