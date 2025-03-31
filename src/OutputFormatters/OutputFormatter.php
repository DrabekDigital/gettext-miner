<?php declare(strict_types=1);
namespace DrabekDigital\GettextMiner\OutputFormatters;

use DrabekDigital\GettextMiner\Exceptions\InvalidConfigurationException;

/**
 * Interface for all output formatters
 *
 * Notes:
 * - Configuration should be passed into constructor by using named arguments
 */
interface OutputFormatter
{
    /**
     * Formats given messages into the desired output format.
     *
     * @param array<string, array<string>> $messages All messages to be dumped in the output.
     * @return string Output formatted to desired format and returned as complete string
     */
    public function format(array $messages): string;

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
