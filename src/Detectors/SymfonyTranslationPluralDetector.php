<?php declare(strict_types=1);
namespace DrabekDigital\GettextMiner\Detectors;

use Nette\Utils\Strings;
use Override;

class SymfonyTranslationPluralDetector implements PluralDetector
{

    /**
     * Detect plural if message contains %count% placeholder.
     */
    #[Override]
    public function hasPluralPlaceholder(string $message): bool
    {
        return Strings::match($message, '#([^\\\\]|^)%count%#ui') !== null;
    }
}
