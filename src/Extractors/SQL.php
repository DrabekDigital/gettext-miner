<?php declare(strict_types=1);
namespace DrabekDigital\GettextMiner\Extractors;

use DrabekDigital\GettextMiner\Utils\Helpers;
use Override;
use function array_reduce;
use function str_ends_with;

/**
 * Extract strings from SQL files that are wrapped in particular comment tags.
 *
 * Beware that it does not support multiline strings!
 */
class SQL implements Extractor
{
    // Example: INSERT INTO enum VALUES (/*_*/'String'/*_*/);
    const string TAG = '/*_*/';
    const string REGEX = '#(__TAG__)(.*?)(__TAG__)#usm';


    public function __construct(
        /** @var string[]  */
        private readonly array $extensions = ['.sql'],
    ) {
    }

    #[Override]
    public function accepts(string $path): bool
    {
        return array_reduce($this->extensions, static fn($carry, $ext) => $carry || str_ends_with($path, $ext), false);
    }

    #[Override]
    public function extract(string $path, string $content, ?string $projectDirectory = null): array
    {
        $messages = [];
        preg_match_all(
            str_replace('__TAG__', preg_quote(static::TAG, '#'), static::REGEX),
            $content,
            $matches,
            PREG_OFFSET_CAPTURE
        );
        if ($matches && isset($matches[2])) {
            foreach ($matches[2] as $occurrence) {
                [$message, $offset] = $occurrence;
                $messages[Helpers::removeSQLStringWrap($message)][] = $path . ':' . Helpers::offsetToLine($content, $offset);
            }
        }
        return $messages;
    }

    #[Override]
    public static function processConfig(array &$constructorArgs): void
    {
    }
}
