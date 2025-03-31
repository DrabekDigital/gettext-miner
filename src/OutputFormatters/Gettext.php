<?php declare(strict_types=1);

namespace DrabekDigital\GettextMiner\OutputFormatters;

use DrabekDigital\GettextMiner\Detectors\PluralDetector;
use DrabekDigital\GettextMiner\Exceptions\InvalidConfigurationException;
use DrabekDigital\GettextMiner\Utils\Helpers;
use DrabekDigital\GettextMiner\Utils\Aliases;
use Nette\Utils\Strings;
use Override;
use Exception;
use Throwable;
use function ksort;
use function sprintf;
use function str_contains;

class Gettext implements OutputFormatter
{

    /** @var array<string, string> */
    private array $meta = [
        'Content-Type' => 'text/plain; charset=UTF-8',  // default encoding
        'Plural-Forms' => 'nplurals=2; plural=(n != 1);'    // default plural form is english
    ];

    /**
     * @param PluralDetector $detector Detector to be used for plural detection
     * @param bool $lineNumbers Whether to include line numbers in the output
     * @param array<string, string> $extraMeta Additional metadata to be included in the gettext header fields
     */
    public function __construct(
        private readonly PluralDetector $detector,
        private readonly bool $lineNumbers = true,
        array $extraMeta = []
    ) {
        foreach ($extraMeta as $key => $value) {
            $this->meta[$key] = $value;
        }
    }

    #[Override]
    public function format(array $messages): string
    {
        ksort($messages);

        $output = [];

        $this->populateGettextTemplateStart($output);

        foreach ($messages as $message => $fromFiles) {
            if (!is_string($message) || Strings::length($message) === 0) { // @phpstan-ignore-line
                continue;
            }

            if ($this->lineNumbers) {
                ksort($fromFiles);

                foreach ($fromFiles as $fromFile) {
                    $escapedPath = Helpers::convertPathToForwardSlashes($fromFile);
                    if (str_contains($escapedPath, ' ')) {
                        // See https://www.gnu.org/software/gettext/manual/html_node/PO-Files.html
                        $escapedPath = "\u{2068}" . $escapedPath . "\u{2069}";
                    }
                    $output[] = '#: ' . $escapedPath;
                }
            }

            $output[] = sprintf('msgid "%s"', Helpers::preprocessGettextNewlines(Helpers::escapeStringToBeWrapped($message)));

            if (!$this->detector->hasPluralPlaceholder($message)) {
                $output[] = sprintf('msgstr "%s"', '');
            } else {
                $output[] = sprintf('msgid_plural "%s"', $message);
                $output[] = sprintf('msgstr[0] "%s"', '');
                $output[] = sprintf('msgstr[1] "%s"', '');
            }

            $output[] = '';
        }

        return implode("\n", $output);
    }

    /**
     * @param array<string> $output
     * @return void
     */
    private function populateGettextTemplateStart(array &$output): void
    {
        $output = [
            sprintf('# Created: %s', date('c')),
            '',
            'msgid ""',
            'msgstr ""',
        ];
        foreach ($this->meta as $key => $value) {
            $output[] = sprintf('"%s: %s\n"', $key, $value);
        }
        $output[] = '';
    }

    public static function processConfig(array &$constructorArgs): void
    {
        if (isset($constructorArgs['detector'])) {
            $detector = $constructorArgs['detector'];
            if (isset(Aliases::PLURAL_DETECTORS[$detector])) {
                $className = Aliases::PLURAL_DETECTORS[$detector];
            } else {
                $className = $detector;
            }
            if (!is_string($className) || !class_exists($className)) {
                throw new InvalidConfigurationException(message: sprintf('class [%s] could not been found.', print_r($className, true)));
            }
            if (!is_a($className, PluralDetector::class, true)) {
                throw new InvalidConfigurationException(message: sprintf('class [%s] is not an PluralDetector.', $className));
            }

            try {
                /** @var PluralDetector $instance */
                $instance = new $className();
            } catch (Throwable $ex) { // @phpstan-ignore-line
                throw new InvalidConfigurationException(sprintf('class [%s] could not been created due to an error: [%s]', $className, $ex->getMessage()), 0, $ex);
            }
            $constructorArgs['detector'] = $instance;
        }
    }
}
