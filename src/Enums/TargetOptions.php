<?php declare(strict_types=1);
namespace DrabekDigital\GettextMiner\Enums;

enum TargetOptions: string
{
    case OUTPUT = 'output';
    case SOURCES = 'sources';
    case FILES = 'files';
    case EXTRACTORS = 'extractors';
}
