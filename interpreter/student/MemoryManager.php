<?php

// MemoryManager.php

require_once 'Argument.php';
require_once 'Variable.php';
require_once 'Exception.php';

use IPP\Student\MissingFrameException;
use IPP\Student\MissingVariableException;
use IPP\Student\OperandTypeErrorException;
use IPP\Student\InvalidOperandValueException;
use IPP\Student\SemanticErrorException;
use IPP\Student\StringErrorException;

class MemoryManager
{
    /** @var Variable[] $globalFrame */
    private array $globalFrame = [];
    /** @var Variable[] $temporaryFrame */
    private ?array $temporaryFrame = null;
    /** @var Variable[][] $frameStack */
    private array $frameStack;

    private bool $tfCreated;

    public function __construct()
    {
        $this->frameStack = [];
        $this->tfCreated = false;
    }

    public function setGlobal(string $varName, mixed $value, mixed $type): void
    {
        $this->globalFrame[$varName] = new Variable($varName, $value, $type);
    }


    public function getGlobal(string $varName): Variable
    {
        if (!isset($this->globalFrame[$varName])) {
            throw new MissingVariableException();
        }
        return $this->globalFrame[$varName];
    }

    public function setLocal(string $varName, mixed $value, mixed $type): void
    {
        // $this->frameStack->top()[$varName] = new Variable($varName, $value, $type);
        $this->frameStack[count($this->frameStack) - 1][$varName] = new Variable($varName, $value, $type);
    }

    public function getLocal(string $varName): Variable
    {
        if (!isset($this->frameStack[count($this->frameStack) - 1][$varName])) {
            throw new MissingVariableException();
        }
        return $this->frameStack[count($this->frameStack) - 1][$varName];
    }

    public function setTemporary(string $varName, mixed $value, mixed $type): void
    {
        // if ($this->temporaryFrame[$varName] instanceof Variable && $value == null && $type == null) {
        //     throw new SemanticErrorException();
        // }
        $this->temporaryFrame[$varName] = new Variable($varName, $value, $type);
    }


    public function getTemporary(string $varName): Variable
    {
        if (!isset($this->temporaryFrame[$varName])) {
            throw new MissingVariableException();
        }
        return $this->temporaryFrame[$varName];
    }

    /**
     * DEFVAR instruction
     * @param Argument $arg
     * @throws MissingFrameException
     * @throws SemanticErrorException
     */
    public function defvar(Argument $arg): void
    {
        $parts = explode("@", (string)$arg->getValue());
        $frame = $parts[0];
        $varName = $parts[1];

        switch ($frame) {
            case "GF":
                if (isset($this->globalFrame[$varName])) {
                    throw new SemanticErrorException();
                }
                $this->globalFrame[$varName] = new Variable($varName, null, null);
                break;
            case "LF":
                if (count($this->frameStack) == 0) {
                    throw new MissingFrameException();
                }
                if (isset($this->frameStack[count($this->frameStack) - 1][$varName])) {
                    throw new SemanticErrorException();
                }
                $this->setLocal($varName, null, null);
                break;
            case "TF":
                if (isset($this->temporaryFrame[$varName])) {
                    throw new SemanticErrorException();
                }
                $this->setTemporary($varName, null, null);
                break;
        }
    }

    /**
     * Finds variable and returns it
     * @param Argument $arg
     * @return Variable
     * @throws MissingFrameException
     */
    public function getVarValue(Argument $arg): Variable
    {
        $parts = explode("@", (string)$arg->getValue());
        $frame = $parts[0];
        $varName = $parts[1];
        switch ($frame) {
            case "GF":
                return $this->getGlobal($varName);
            case "LF":
                return $this->getLocal($varName);
            case "TF":
                return $this->getTemporary($varName);
            default:
                throw new MissingFrameException();
        }
    }

    /**
     * Finds and returns value of symbol (variable or constant)
     * @param Argument $arg
     * @return mixed
     */
    public function getSymbValue(Argument $arg): mixed
    {
        if ($arg->getType() == "var") {
            return $this->getVarValue($arg)->getValue();
        } else {
            return $arg->getValue();
        }
    }

    /**
     * Finds and returns type of symbol (variable or constant)
     * @param Argument $arg
     * @return mixed
     */
    public function getSymbType(Argument $arg): mixed
    {
        if ($arg->getType() == "var") {
            return $this->getVarValue($arg)->getType();
        } else {
            return $arg->getType();
        }
    }

    /**
     * MOVE instruction
     * @param Argument $arg1
     * @param Argument $arg2
     * @throws OperandTypeErrorException
     * @throws MissingFrameException
     */
    public function move(Argument $arg1, Argument $arg2): void
    {
        $parts1 = explode("@", $arg1->getValue());
        $frame1 = $parts1[0];
        $varName1 = $parts1[1];

        $type = $this->getSymbType($arg2);

        $value = $this->getSymbValue($arg2);
        switch ($type) {
            case "int":
                $value = (int)$value;
                if (!is_numeric($value)) {
                    throw new OperandTypeErrorException();
                }
                break;
            case "bool":
                if ($value != true && $value != false) {
                    throw new OperandTypeErrorException();
                }
                break;
            case "string":
                break;
            case "nil":
                $value = null;
                break;
            default:
                throw new OperandTypeErrorException();
        }

        switch ($frame1) {
            case "GF":
                $this->setGlobal($varName1, $value, $type);
                break;
            case "LF":
                $this->setLocal($varName1, $value, $type);
                break;
            case "TF":
                $this->setTemporary($varName1, $value, $type);
                break;
            default:
                exit(32);
        }
    }


    /**
     * TYPE instruction
     * @param Argument $arg1
     * @param Argument $arg2
     * @return void
     */
    public function type(Argument $arg1, Argument $arg2): void
    {
        $var1 = $this->getSymbValue($arg2);
        $type = $this->getSymbType($arg2);

        $this->move($arg1, new Argument("string", $type));
    }

    /**
     * GETCHAR instruction
     * @param Argument $arg1
     * @param Argument $arg2
     * @param Argument $arg3
     * @return void
     * @throws OperandTypeErrorException
     * @throws StringErrorException
     */
    public function getChar(Argument $arg1, Argument $arg2, Argument $arg3): void
    {
        if ($this->getSymbType($arg2) != "string" || $this->getSymbType($arg3) != "int") {
            throw new OperandTypeErrorException();
        }

        $var1 = $this->getSymbValue($arg2);
        $var2 = $this->getSymbValue($arg3);

        if ($var2 < 0 || $var2 >= strlen($var1)) {
            throw new StringErrorException();
        }

        $char = $var1[$var2];

        $this->move($arg1, new Argument("string", $char));
    }

    /**
     * SETCHAR instruction
     * @param Argument $arg1
     * @param Argument $arg2
     * @param Argument $arg3
     * @return void
     * @throws OperandTypeErrorException
     * @throws StringErrorException
     */
    public function setChar(Argument $arg1, Argument $arg2, Argument $arg3): void
    {
        if (($arg1->getType() != "var" && $this->getSymbType($arg1) != "string") || $this->getSymbType($arg2) != "int" || $this->getSymbType($arg3) != "string") {
            throw new OperandTypeErrorException();
        }

        $var1 = $this->getSymbValue($arg1);
        $var2 = $this->getSymbValue($arg2);
        $var3 = $this->getSymbValue($arg3);

        if ($var2 < 0 || $var2 >= strlen($var1) || $var3 == "") {
            throw new StringErrorException();
        }

        $var1[$var2] = $var3;

        $this->move($arg1, new Argument("string", $var1));
    }

    /**
     * STR2INT instruction
     * @param Argument $arg1
     * @param Argument $arg2
     * @param Argument $arg3
     * @return void
     * @throws OperandTypeErrorException
     * @throws StringErrorException
     */
    public function stritoint(Argument $arg1, Argument $arg2, Argument $arg3): void
    {
        if ($this->getSymbType($arg2) != "string" || $this->getSymbType($arg3) != "int") {
            throw new OperandTypeErrorException();
        }

        $var1 = $this->getSymbValue($arg2);
        $var2 = $this->getSymbValue($arg3);

        if ($var2 < 0 || $var2 >= strlen($var1)) {
            throw new StringErrorException();
        }

        $char = $var1[$var2];

        $this->move($arg1, new Argument("int", ord($char)));
    }

    /**
     * CREATEFRAME instruction
     * @return void
     */
    public function createFrame(): void
    {
        $this->temporaryFrame = [];
        $this->tfCreated = true;
    }

    /**
     * PUSHFRAME instruction
     * @return void
     * @throws MissingFrameException
     */
    public function pushFrame(): void
    {
        if (!$this->tfCreated) {
            throw new MissingFrameException();
        }
        array_push($this->frameStack, $this->temporaryFrame);
        $this->temporaryFrame = null;
        $this->tfCreated = false;
    }

    /**
     * POPFRAME instruction
     * @return void
     * @throws MissingFrameException
     */
    public function popFrame(): void
    {
        if (count($this->frameStack) == 0) {
            throw new MissingFrameException();
        }
        $this->temporaryFrame = array_pop($this->frameStack);
    }

    /**
     * JUMPIFEQ instruction
     * @param Argument $arg2
     * @param Argument $arg3
     * @return bool
     * @throws OperandTypeErrorException
     */
    public function jumpifeq(Argument $arg2, Argument $arg3): bool
    {
        if ($this->getSymbType($arg2) != $this->getSymbType($arg3)) {
            throw new OperandTypeErrorException();
        }

        $var1 = $this->getSymbValue($arg2);
        $var2 = $this->getSymbValue($arg3);

        return $var1 == $var2;
    }

    /**
     * JUMPIFNEQ instruction
     * @param Argument $arg2
     * @param Argument $arg3
     * @return bool
     * @throws OperandTypeErrorException
     */
    public function jumpifneq(Argument $arg2, Argument $arg3): bool
    {
        if ($this->getSymbType($arg2) != $this->getSymbType($arg3)) {
            throw new OperandTypeErrorException();
        }

        $var1 = $this->getSymbValue($arg2);
        $var2 = $this->getSymbValue($arg3);

        return $var1 != $var2;
    }


    /**
     * LESS_THAN instruction
     * @param Argument $arg1
     * @param Argument $arg2
     * @param Argument $arg3
     * @return void
     * @throws OperandTypeErrorException
     */
    public function lessthan(Argument $arg1, Argument $arg2, Argument $arg3): void
    {
        if ($this->getSymbType($arg2) != $this->getSymbType($arg3)) {
            throw new OperandTypeErrorException();
        }
        if ($this->getSymbType($arg2) == "nil" || $this->getSymbType($arg3) == "nil") {
            throw new OperandTypeErrorException();
        }

        $var1 = $this->getSymbValue($arg2);
        $var2 = $this->getSymbValue($arg3);

        $result = $var1 < $var2 ? 'true' : 'false';

        $this->move($arg1, new Argument("bool", $result));
    }

    /**
     * GREATER_THAN instruction
     * @param Argument $arg1
     * @param Argument $arg2
     * @param Argument $arg3
     * @return void
     * @throws OperandTypeErrorException
     */
    public function greatthan(Argument $arg1, Argument $arg2, Argument $arg3): void
    {
        if ($this->getSymbType($arg2) == "nil" || $this->getSymbType($arg3) == "nil") {
            throw new OperandTypeErrorException();
        }

        if ($this->getSymbType($arg2) != $this->getSymbType($arg3)) {
            throw new OperandTypeErrorException();
        }

        $var1 = $this->getSymbValue($arg2);
        $var2 = $this->getSymbValue($arg3);

        $result = $var1 > $var2 ? 'true' : 'false';

        $this->move($arg1, new Argument("bool", $result));
    }

    /**
     * EQUAL instruction
     * @param Argument $arg1
     * @param Argument $arg2
     * @param Argument $arg3
     * @return void
     * @throws OperandTypeErrorException
     */
    public function equal(Argument $arg1, Argument $arg2, Argument $arg3): void
    {
        $var1 = $this->getSymbValue($arg2);
        $var2 = $this->getSymbValue($arg3);

        if ($this->getSymbType($arg2) != "nil" && $this->getSymbType($arg3) != "nil") {
            if ($this->getSymbType($arg2) != $this->getSymbType($arg3)) {
                throw new OperandTypeErrorException();
            }
        }

        $result = $var1 === $var2 ? 'true' : 'false';

        $this->move($arg1, new Argument("bool", $result));
    }

    /**
     * NOT instruction
     * @param Argument $arg1
     * @param Argument $arg2
     * @return void
     * @throws OperandTypeErrorException
     */
    public function not(Argument $arg1, Argument $arg2): void
    {
        $var1 = $this->getSymbValue($arg2);

        if ($this->getSymbType($arg2) != "bool") {
            throw new OperandTypeErrorException();
        }

        $result = $var1 == true ? 'false' : 'true';

        $this->move($arg1, new Argument("bool", $result));
    }

    /**
     * OR instruction
     * @param Argument $arg1
     * @param Argument $arg2
     * @param Argument $arg3
     * @return void
     * @throws OperandTypeErrorException
     */
    public function or(Argument $arg1, Argument $arg2, Argument $arg3): void
    {
        if ($this->getSymbType($arg2) != "bool" || $this->getSymbType($arg3) != "bool") {
            throw new OperandTypeErrorException();
        }

        $var1 = $this->getSymbValue($arg2);
        $var2 = $this->getSymbValue($arg3);

        if ($var1 == true || $var2 == true) {
            $this->move($arg1, new Argument("bool", 'true'));
        } else {
            $this->move($arg1, new Argument("bool", 'false'));
        }
    }

    /**
     * AND instruction
     * @param Argument $arg1
     * @param Argument $arg2
     * @param Argument $arg3
     * @return void
     * @throws OperandTypeErrorException
     */
    public function and(Argument $arg1, Argument $arg2, Argument $arg3): void
    {
        if ($this->getSymbType($arg2) != "bool" || $this->getSymbType($arg3) != "bool") {
            throw new OperandTypeErrorException();
        }

        $var1 = $this->getSymbValue($arg2);
        $var2 = $this->getSymbValue($arg3);

        if ($var1 == true && $var2 == true) {
            $this->move($arg1, new Argument("bool", 'true'));
        } else {
            $this->move($arg1, new Argument("bool", 'false'));
        }
    }

    /**
     * EXIT instruction
     * @param Argument $arg1
     * @return void
     * @throws OperandTypeErrorException
     * @throws InvalidOperandValueException
     */
    public function exit_command(Argument $arg1): void
    {
        if ($this->getSymbType($arg1) != "int") {
            //TODO: throw exception
            throw new OperandTypeErrorException();
        }
        $var1 = $this->getSymbValue($arg1);

        if (!is_int($var1)) {
            throw new InvalidOperandValueException();
        }
        if ((int)$var1 < 0 || (int)$var1 > 9) {
            throw new InvalidOperandValueException();
        }

        exit((int)$var1);
    }

    /**
     * ADD instruction
     * @param Argument $arg1
     * @param Argument $arg2
     * @param Argument $arg3
     * @return void
     * @throws OperandTypeErrorException
     */
    public function addSymbols(Argument $arg1, Argument $arg2, Argument $arg3): void
    {

        if ($this->getSymbType($arg2) != "int" || $this->getSymbType($arg3) != "int") {
            throw new OperandTypeErrorException();
        }

        $var1 = $this->getSymbValue($arg2);
        $var2 = $this->getSymbValue($arg3);

        $result = $var1 + $var2;

        $this->move($arg1, new Argument("int", $result));
    }

    /**
     * SUB instruction
     * @param Argument $arg1
     * @param Argument $arg2
     * @param Argument $arg3
     * @return void
     * @throws OperandTypeErrorException
     */
    public function subSymbols(Argument $arg1, Argument $arg2, Argument $arg3): void
    {
        if ($this->getSymbType($arg2) != "int" || $this->getSymbType($arg3) != "int") {
            throw new OperandTypeErrorException();
        }

        $var1 = $this->getSymbValue($arg2);
        $var2 = $this->getSymbValue($arg3);

        $result = $var1 - $var2;

        $this->move($arg1, new Argument("int", $result));
    }

    /**
     * MUL instruction
     * @param Argument $arg1
     * @param Argument $arg2
     * @param Argument $arg3
     * @return void
     * @throws OperandTypeErrorException
     */
    public function mulSymbols(Argument $arg1, Argument $arg2, Argument $arg3): void
    {
        if ($this->getSymbType($arg2) != "int" || $this->getSymbType($arg3) != "int") {
            throw new OperandTypeErrorException();
        }

        $var1 = $this->getSymbValue($arg2);
        $var2 = $this->getSymbValue($arg3);

        $result = $var1 * $var2;

        $this->move($arg1, new Argument("int", $result));
    }

    /**
     * IDIV instruction
     * @param Argument $arg1
     * @param Argument $arg2
     * @param Argument $arg3
     * @return void
     * @throws OperandTypeErrorException
     * @throws InvalidOperandValueException
     */
    public function idivSymbols(Argument $arg1, Argument $arg2, Argument $arg3): void
    {
        if ($this->getSymbType($arg2) != "int" || $this->getSymbType($arg3) != "int") {
            throw new OperandTypeErrorException();
        }

        $var1 = $this->getSymbValue($arg2);
        $var2 = $this->getSymbValue($arg3);

        if ($var2 == 0) {
            throw new InvalidOperandValueException();
        }

        $result = $var1 / $var2;

        $this->move($arg1, new Argument("int", $result));
    }

    /**
     * INT2CHAR instruction
     * @param Argument $arg1
     * @param Argument $arg2
     * @return void
     * @throws OperandTypeErrorException
     * @throws StringErrorException
     */
    public function int2char(Argument $arg1, Argument $arg2): void
    {
        if ($this->getSymbType($arg2) != "int") {
            throw new OperandTypeErrorException();
        }

        $var1 = $this->getSymbValue($arg2);

        // Check if int is in range
        if ($var1 < 0 || $var1 > 255) {
            throw new StringErrorException();
        }

        // Convert int to \xxx format
        $char = "\\" . str_pad($var1, 3, '0', STR_PAD_LEFT);

        $this->move($arg1, new Argument("string", $char));
    }

    public function printAllFrames(): void
    {
        $length = 30;
        echo "\n" . str_repeat("_", $length) . "\n";
        echo "| Global frame:" . str_repeat(" ", $length - 15) . "|" . PHP_EOL;
        foreach ($this->globalFrame as $varName => $value) {
            echo "| " . $value . str_repeat(" ", $length - strlen($value) - 2) . "|" . PHP_EOL;
        }

        if (!empty($this->frameStack)) {
            echo "\n" . str_repeat("_", $length) . "\n";
            echo "| Local frames:" . str_repeat(" ", $length - 15) . "|" . PHP_EOL;
            for ($i = count($this->frameStack) - 1; $i >= 0; $i--) {
                echo "| Frame " . $i . str_repeat(" ", $length - strlen("Frame " . $i) - 2) . "|" . PHP_EOL;
                foreach ($this->frameStack[$i] as $varName => $value) {
                    echo "| " . $value . str_repeat(" ", $length - strlen($value) - 2) . "|" . PHP_EOL;
                }
            }
        }

        if (!empty($this->temporaryFrame)) {
            echo "\n" . str_repeat("_", $length) . "\n";
            echo "Temporary frame:" . PHP_EOL;
            foreach ($this->temporaryFrame as $varName => $value) {
                echo "| " . $value . str_repeat(" ", $length - strlen($value) - 2) . "|" . PHP_EOL;
            }
        }

        echo str_repeat("_", $length) . "\n";
    }
}
