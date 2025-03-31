<?php declare(strict_types=1);
namespace DrabekDigital\GettextMiner\Extractors;

use DrabekDigital\GettextMiner\Exceptions\InvalidConfigurationException;

/**
 * Interface for all translation strings extractors
 *
 * Notes:
 * - Should not open and read files by itself (for better testability)
 * - Should be able to extract strings from one group of input resources (such as PHP files...)
 * - Configuration should be done via constructor
 */
interface Extractor
{
    /**
     * Returns true iff the file is acceptable for processing by this extractor
     *
     * @param string $path Path to file
     * @return bool
     */
    public function accepts(string $path): bool;

    /**
     * Extracts all translation strings from a file
     *
     * @param string $path Path to file
     * @param string $content File content
     * @param string|null $projectDirectory Project directory
     * @return array<string, array<string>> List of extracted translation strings
     */
    public function extract(string $path, string $content, ?string $projectDirectory = null): array;

    /**
     * Process configuration before passing it to the constructor
     * Note: this is the right point for instatiation of classes if needed
     *
     * @param array<string, mixed> $constructorArgs
     * @return void
     * @throws InvalidConfigurationException
     */
    public static function processConfig(array &$constructorArgs): void;
}
