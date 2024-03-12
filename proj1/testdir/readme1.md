# Parse.py Documentation

## Overview

- **File:** parse.py
- **Author:** Boris Semanco (xseman06)
- **Description:** This program parses the IPPcode24 from stdin to XML format

## Functions

1. `AddInstruction(parent, opcode, token_counter)`

   - **Description:** Adds an instruction to the XML
   - **Parameters:**
     - `parent`: Parent element to add the instruction to
     - `opcode`: Operation code of the instruction
     - `token_counter`: Counter for the order of the instruction
   - **Returns:** The added instruction element

2. `IsEnoughArgs(words, wanted_count)`

   - **Description:** Checks if the number of arguments is correct
   - **Parameters:**
     - `words`: List of arguments
     - `wanted_count`: Expected number of arguments
   - **Returns:** Boolean value indicating whether the count matches

3. `StringParse(string)`

   - **Description:** Checks if the string is in valid format and replace skecthy characters
   - **Parameters:**
     - `string`: Input string to parse/validate
   - **Returns:** Parsed string

4. `CheckLabelName(label)`

   - **Description:** Checks if the label name is in valid format
   - **Parameters:**
     - `label`: Label name to check
   - **Returns:** Boolean value indicating whether the label name is valid

5. `GetTypeAndText(arg)`

   - **Description:** Returns the type and text of the argument
   - **Parameters:**
     - `arg`: Argument to analyze.
   - **Returns:** List containing argument type and text.

6. `CheckVarName(var)`

   - **Description:** Checks if the variable name is in valid format
   - **Parameters:**
     - `var`: Variable name to check
   - **Returns:** Boolean value indicating whether the variable name is valid

7. `CheckVar(var)`

   - **Description:** Checks if the passes argument is really variable
   - **Parameters:**
     - `var`: Variable to check
   - **Returns:** Valid variable

8. `PrintHelp()`

   - **Description:** Prints help information for the program

9. `Parse()`
   - **Description:** Parses the input to XML format

## Error Codes

- `INVALID_ARGS`: Incorrect command-line parameters
- `MISSING_HEADER`: Missing or incorrect header in the IPPcode24 source code
- `INVALID_OPERATION`: Unknown or invalid operation code in the IPPcode24 source code
- `OTHER_LEX_SYNT`: Other lexical or syntactical errors in the IPPcode24 source code

## Execution

- The script can be executed with the command `python3 parse.py < input_file > output_file`
- Use `--help` argument for program usage information
