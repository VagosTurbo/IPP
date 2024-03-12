# file: parse.py
# author: Boris Semanco (xseman06)

import sys
import xml.etree.ElementTree as ET
import re


# error codes
INVALID_ARGS = 10
MISSING_HEADER = 21
INVALID_OPERATION = 22
OTHER_LEX_SYNT = 23


# adds instruction to the xml
def AddInstruction(parent, opcode, token_counter):
    instruction = ET.SubElement(parent, 'instruction', order=str(token_counter), opcode=opcode)
    return instruction

# checks if the number of arguments is correct
def IsEnoughArgs(words, wanted_count):
    if len(words) == wanted_count:
        return True
    else:
        print("Wrong number of arguments", file=sys.stderr)
        sys.exit(OTHER_LEX_SYNT)
        

# checks if the string is valid
def StringParse(string):
    # iterate through the string and check for invalid characters
    for i, char in enumerate(string):
        if char == '<':
            char = "&lt;"
        elif char == '>':
            char = "&gt;"
        elif char == '&':
            char = "&amp;"
        elif char == '\\':
            if i + 3 >= len(string) or  not string[i + 1:i + 4].isdigit():
                print("Invalid escape sequence", file=sys.stderr)
                sys.exit(OTHER_LEX_SYNT)

    return string
            
# checks if the label name is valid
def CheckLabelName(label):
    pattern = r'^[-_$&%*!?][_$&%*!?a-zA-Z]*$|^[a-zA-Z][a-zA-Z]*$'
    return bool(re.match(pattern, label))

# returns the type and text of the argument
def GetTypeAndText(arg):
    splitted = arg.split('@')
    first = splitted[0]
    lefted = '@'.join(splitted[1:])
    arg_type = ''
    arg_text = ''

    if first in ['GF', 'LF', 'TF']:
        arg_type = 'var'
        arg_text = arg

    elif first == 'int':
        arg_type = first
        # error if there is nothing after @
        if lefted == '':
            print("Invalid argument", file=sys.stderr)
            sys.exit(OTHER_LEX_SYNT)

        # check format of the integer
        if len(lefted) > 2:
            index = 1
            
            # check for negative nums
            if lefted[0] == '-':
                index = 2

            if lefted[index] == 'o': 
                # check for octal format
                if not lefted[(index+1):].isdigit() or '8' in lefted or '9' in lefted:
                    print("Invalid octal number", file=sys.stderr)
                    sys.exit(OTHER_LEX_SYNT)
            elif lefted[index] == 'x':
                # check for hexadecimal format
                if not lefted[(index+1):].isalnum():
                    print("Invalid hexadecimal number", file=sys.stderr)
                    sys.exit(OTHER_LEX_SYNT)
                # else check for invalid characters
            elif not lefted[index:].isdigit():
                print("Invalid integer value", file=sys.stderr)
                sys.exit(OTHER_LEX_SYNT)

        arg_text = lefted
        
    # only valid nil value is nil@nil
    elif first == 'nil':
        arg_type = 'nil'
        if lefted != 'nil':
            print("Invalid nil value")
            sys.exit(OTHER_LEX_SYNT)
        else:
            arg_text = lefted

    # only valid boolean values are true and false in any case
    elif first == 'bool':
        arg_type = 'bool'
        if lefted.lower() not in ['true', 'false']:
            print("Invalid boolean value", file=sys.stderr)
            sys.exit(OTHER_LEX_SYNT)
        arg_text = lefted

    elif splitted[0] == 'string':
        arg_type = 'string'
        arg_text = StringParse(lefted)

    else:
        print("Invalid argument type", file=sys.stderr)
        sys.exit(OTHER_LEX_SYNT)
    

    return [arg_type, arg_text]

# checks if the variable name is valid
def CheckVarName(var):
    pattern = r'^[-_$&%*!?a-zA-Z][_$&%*!?a-zA-Z0-9]*$'
    return bool(re.match(pattern, var))

# checks if the variable is valid
def CheckVar(var):
    frame = var.split('@')
    if frame[0] not in ["GF", "LF", "TF"]:
        sys.exit(OTHER_LEX_SYNT)

    if len(frame) != 2:
        sys.exit(OTHER_LEX_SYNT)

    if not CheckVarName(frame[1]):
        sys.exit(OTHER_LEX_SYNT)
        
    return var

# prints help for --help argument
def PrintHelp():
    print("This program parses the IPPcode24 from stdin to XML format")
    print("Usage: python3 parse.py < input_file > output_file")

# parses the input to XML
def Parse():    
    # checks for --help
    if len(sys.argv) == 2:
        if sys.argv[1] == "--help":
            PrintHelp()
            sys.exit(0)
        else:
            print("Invalid argument", file=sys.stderr)
            sys.exit(INVALID_ARGS)
    elif len(sys.argv) > 2:
        print("Invalid number of arguments", file=sys.stderr)
        sys.exit(INVALID_ARGS)

    # reads the stdin and splits it into lines
    raw_lines = sys.stdin.readlines()

    xml_out = ET.Element('program', language="IPPcode24")

    token_counter = 1
    header_found = False

    for line in raw_lines:

        # removes comments and whitespaces
        line = line.split("#")[0].strip()

        args = line.split(" ")
        opcode = args[0]
        args = args[1:]

        # skips empty lines
        if opcode == "":
            continue

        if opcode == ".IPPcode24":
            if header_found:
                print("Multiple headers", file=sys.stderr)
                sys.exit(OTHER_LEX_SYNT)
            else:
                header_found = True
            continue

        # if header was not found as the first non-empty line, exit
        if not header_found:
            print("Header not found", file=sys.stderr)
            sys.exit(MISSING_HEADER)


        opcode = opcode.upper()

        # just opcode
        if opcode in ["CREATEFRAME", "PUSHFRAME", "POPFRAME", "BREAK", "RETURN"]:
            if IsEnoughArgs(args, 0):
                AddInstruction(xml_out, opcode, token_counter)
                token_counter += 1

        # opcode label
        elif opcode in ["LABEL", "JUMP", "CALL"]:
            # checks if label have a name
            if IsEnoughArgs(args, 1):
                instruction = AddInstruction(xml_out, opcode, token_counter)
                token_counter += 1

                if not CheckLabelName(args[0]):
                    print("Invalid label name", file=sys.stderr)
                    sys.exit(OTHER_LEX_SYNT)

                ET.SubElement(instruction, 'arg1', type='label').text = args[0]

        # opcode label symb1 symb2
        elif opcode in ["JUMPIFEQ", "JUMPIFNEQ"]:
            if IsEnoughArgs(args, 3):
                instruction = AddInstruction(xml_out, opcode, token_counter)
                token_counter += 1

                if not CheckLabelName(args[0]):
                    print("Invalid label name", file=sys.stderr)
                    sys.exit(OTHER_LEX_SYNT)

                ET.SubElement(instruction, 'arg1', type='label').text = args[0]
                for i in range(1, 3):
                    arg_type, arg_text = GetTypeAndText(args[i])
                    ET.SubElement(instruction, 'arg' + str(i + 1), type=arg_type).text = arg_text

        # opcode symb
        elif opcode in ["WRITE", "PUSHS", "EXIT", "DPRINT"]:
            if IsEnoughArgs(args, 1):
                instruction = AddInstruction(xml_out, opcode, token_counter)
                token_counter += 1
                arg_type, arg_text = GetTypeAndText(args[0])
                ET.SubElement(instruction, 'arg1', type=arg_type).text = arg_text
                
        # opcode var
        elif opcode in ["DEFVAR", "POPS"]:
            if IsEnoughArgs(args, 1):
                instruction = AddInstruction(xml_out, opcode, token_counter)
                token_counter += 1
                ET.SubElement(instruction, 'arg1', type='var').text = CheckVar(args[0])

        # opcode var symb
        elif opcode in ["MOVE", "INT2CHAR", "STRLEN", "TYPE", "NOT"]:
            if IsEnoughArgs(args, 2):
                instruction = AddInstruction(xml_out, opcode, token_counter)
                token_counter += 1
                
                # var
                ET.SubElement(instruction, 'arg1', type='var').text = CheckVar(args[0])
                # symb
                arg_type, arg_text = GetTypeAndText(args[1])
                ET.SubElement(instruction, 'arg2', type=arg_type).text = arg_text

        # opcode var symb1 symb2
        elif opcode in ["ADD", "SUB", "MUL", "IDIV", "LT", "GT", "EQ", "AND", "OR", "STRI2INT", "CONCAT", "GETCHAR", "SETCHAR"]:
            if IsEnoughArgs(args, 3):
                instruction = AddInstruction(xml_out, opcode, token_counter)
                token_counter += 1
                ET.SubElement(instruction, 'arg1', type='var').text = CheckVar(args[0])
                for i in range(1, 3):
                    arg_type, arg_text = GetTypeAndText(args[i])
                    ET.SubElement(instruction, 'arg' + str(i + 1), type=arg_type).text = arg_text

        # opcode var type
        elif opcode == "READ":
            if IsEnoughArgs(args, 2):
                instruction = AddInstruction(xml_out, opcode, token_counter)
                token_counter += 1
                ET.SubElement(instruction, 'arg1', type='var').text = CheckVar(args[0])

                if args[1] not in ["int", "bool", "string"]:
                    sys.exit(OTHER_LEX_SYNT)

                ET.SubElement(instruction, 'arg2', type='type').text = args[1]
        
        else:
            print("Invalid opcode", file=sys.stderr)
            sys.exit(INVALID_OPERATION)

    ET.indent(xml_out)
    xml_str = ET.tostring(xml_out, encoding='UTF-8', xml_declaration=True)
    sys.stdout.buffer.write(xml_str)

if __name__ == "__main__":
    Parse()

