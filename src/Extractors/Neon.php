<?php declare(strict_types=1);
namespace DrabekDigital\GettextMiner\Extractors;

use DrabekDigital\GettextMiner\Exceptions\InvalidNeonSelectorException;
use DrabekDigital\GettextMiner\Utils\Helpers;
use Nette\Neon\Neon as NetteNeon;
use Nette\Utils\Strings;
use Override;
use function array_reduce;
use function is_string;
use function str_ends_with;

/**
 * Extracts strings from Neon files based on the given paths.
 */
class Neon implements Extractor
{
    /** @var array<string, array<string>> */
    private array $paths = [];

    /**
     * @param array<string, array<string>> $paths
     * @param array<string> $extensions
     */
    public function __construct(
        array $paths,
        private readonly array $extensions = ['.neon'],
    ) {
        foreach ($paths as $file => $selectors) {
            foreach ($selectors as $selector) {
                if (!$this->isPathSelector($selector)) {
                    throw new \DrabekDigital\GettextMiner\Exceptions\InvalidConfigurationException("Selector `{$selector}` is not a valid path within neon file.");
                }
            }
            $this->paths[$file] = $selectors;
        }
    }

    private function isPathSelector(string $path): bool
    {
        return Strings::match($path, '/^(([]\w\d\-_]+)\|)+([\w\d\-_]+)$/ui') !== null;
    }

    public function accepts(string $path): bool
    {
        return array_reduce($this->extensions, static fn($carry, $ext) => $carry || str_ends_with($path, $ext), false);
    }

    public function extract(string $path, string $content, ?string $projectDirectory = null): array
    {
        /** @var array<string, mixed> $parsed */
        $parsed = NetteNeon::decode($content);
        if (!is_array($parsed)) { // @phpstan-ignore-line
            return [];
        }

        $messages = [];

        foreach ($this->paths as $targetFilePath => $pathSelectors) {
            if (Helpers::convertPathToForwardSlashes($path) !== Helpers::convertPathToForwardSlashes("{$projectDirectory}/{$targetFilePath}")) {
                continue;
            }
            foreach ($pathSelectors as $pathSelector) {
                $path2 = explode('|', $pathSelector);
                $result = Helpers::getRecursiveFromArrayByPath($parsed, $path2);

                if (is_string($result)) {
                    $messages[$result][] = $path;
                } elseif (is_array($result)) {
                    foreach ($result as $message) {
                        if (!is_string($message)) {
                            continue;
                        }
                        $messages[$message][] = $path;
                    }
                } else {
                    throw new InvalidNeonSelectorException("Selector `{$pathSelector}` is in unsupported format.");
                }
            }
        }
        return $messages;
    }

    #[Override]
    public static function processConfig(array &$constructorArgs): void
    {
    }
}
