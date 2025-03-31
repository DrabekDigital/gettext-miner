<?php declare(strict_types=1);

namespace DrabekDigital\GettextMiner\Utils;

use DrabekDigital\GettextMiner\Detectors\SprintfPluralDetector;
use DrabekDigital\GettextMiner\OutputFormatters\Gettext;
use DrabekDigital\GettextMiner\OutputFormatters\ArrayFile;
use DrabekDigital\GettextMiner\Detectors\SymfonyTranslationPluralDetector;

final class Aliases
{
    private function __construct()
    {
    }

    /** @var array<string, string> */
    public const array PLURAL_DETECTORS = [
        'SymfonyTranslationPluralDetector' => SymfonyTranslationPluralDetector::class,
        'SprintfPluralDetector' => SprintfPluralDetector::class,
    ];

    public const array OUTPUT_FORMATTERS = [
        'ArrayFile' => ArrayFile::class,
        'Gettext' => Gettext::class,
    ];

    public const array EXTRACTORS = [
        'PHP' => \DrabekDigital\GettextMiner\Extractors\PHP::class,
        'LegacyLatte' => \DrabekDigital\GettextMiner\Extractors\LegacyLatte::class,
        'Neon' => \DrabekDigital\GettextMiner\Extractors\Neon::class,
        'SQL' => \DrabekDigital\GettextMiner\Extractors\SQL::class,
        'Nette' => \DrabekDigital\GettextMiner\Extractors\Nette::class,
    ];
}
