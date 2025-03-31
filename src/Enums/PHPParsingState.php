<?php declare(strict_types=1);

namespace DrabekDigital\GettextMiner\Enums;

enum PHPParsingState: int
{
    case START = 0;
    case FUNCTIONS_FOLLOWS = 1;
    case FUNCTION_MATCHES = 2;
    case ARGUMENTS_FOLLOWS = 3;
    case ARGUMENT_MATCHING = 4;
    case EXTRACT_STRING = 5;
}
