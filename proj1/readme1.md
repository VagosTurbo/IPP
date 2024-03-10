# Parser

## Description

This program, `parse.py`, is designed to parse the IPPcode24 from stdin to XML format.

## Author

- Boris Semanco (xseman06)

## Usage

python3 parse.py < input_file > output_file

## Error Codes

- `INVALID_ARGS (10)`: Incorrect command-line parameters.
- `MISSING_HEADER (21)`: Incorrect or missing header in the source code written in IPPcode24.
- `INVALID_OPERATION (22)`: Unknown or incorrect operation code in the source code written in IPPcode24.
- `OTHER_LEX_SYNT (23)`: Other lexical or syntactical errors in the source code written in IPPcode24.

## Functionality

- Parses IPPcode24 from stdin to XML format.
- Handles various instructions and arguments specified in IPPcode24.
