<?php declare(strict_types=1);
namespace DrabekDigital\GettextMiner\Detectors;

use Nette\Utils\Strings;
use Override;

class SprintfPluralDetector implements PluralDetector
{
    /**
     * Detect plural if message contains %d placeholder.
     */
    #[Override]
    public function hasPluralPlaceholder(string $message): bool
    {
        return Strings::match($message, '#([^\\\\]|^)%d#ui') !== null;
    }
}
