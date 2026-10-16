<?php declare(strict_types=1);
namespace DrabekDigital\GettextMiner\OutputFormatters;

use DrabekDigital\GettextMiner\Enums\Indent;
use DrabekDigital\GettextMiner\Exceptions\InvalidConfigurationException;
use DrabekDigital\GettextMiner\Utils\Helpers;
use Nette\Utils\Strings;
use Override;
use function ksort;
use const SORT_STRING;

readonly class ArrayFile implements OutputFormatter
{
    /**
     * @param string $outputVariable Name of the variable to be used in the output file
     * @param Indent $indent Indentation type
     */
    public function __construct(
        private string $outputVariable = 'messages',
        private Indent $indent = Indent::SPACES
    ) {
    }

    #[Override]
    public function format(array $messages): string
    {
        ksort($messages, SORT_STRING);

        $output= ['<?php declare(strict_types=1);'];
        $output[] = sprintf('$%s = [', $this->outputVariable);

        // Metadata about file origin are ignored at the moment
        foreach ($messages as $message => $occurrences) {
            $message = (string) $message; // numeric string keys are converted to int by PHP
            if (Strings::length($message) === 0) {
                continue;
            }
            $output[] = sprintf(
                "%s'%s',",
                $this->indent === Indent::SPACES ? '    ' : "\t",
                Helpers::escapeStringToBeWrapped($message, "'")
            );
        }
        $output[] = '];';
        return implode("\n", $output);
    }

    public static function processConfig(array &$constructorArgs): void
    {
        if (isset($constructorArgs['indent']) && is_string($constructorArgs['indent'])) {
            $constructorArgs['indent'] = Indent::tryFrom($constructorArgs['indent']) ?? throw new InvalidConfigurationException('Invalid indent type');
        }
    }
}
