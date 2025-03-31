<?php declare(strict_types=1);
namespace DrabekDigital\GettextMiner\Extractors;

use DrabekDigital\GettextMiner\Utils\Helpers;
use Override;
use DrabekDigital\GettextMiner\Exceptions\InvalidStateException;
use DrabekDigital\GettextMiner\Enums\PHPParsingState;

/**
 * Extracts operands from (gettext) functions in PHP files.
 */
class PHP implements Extractor
{
    const string EXPLICIT_TAG = '/*gettext-miner*/';

    /**
     * @param array<string, int> $extraFunctions
     * @param array<string, int> $functions
     * @param array<string> $extensions
     */
    public function __construct(
        array $extraFunctions = [],
        private array $functions = [
            'translate' => 1,
            '_'         => 1
        ],
        private readonly array $extensions = ['.php'],
    ) {
        foreach ($extraFunctions as $functionName => $argumentPosition) {
            $this->functions[$functionName] =(int) $argumentPosition;
        }
    }

    #[Override]
    public function extract(string $path, string $content, ?string $projectDirectory = null): array
    {
        // Parse the PHP code
        $tokens = token_get_all($content);

        $messages = [];

        // States for the state machine
        $state = PHPParsingState::START;
        $argumentPositionOffset = 0;
        $lastString = null;
        $lastLine = null;
        for ($i = 0; $i < count($tokens); $i++) {
            $token = $tokens[$i];
            // Skipped tokens (whitespace, comments...)
            if (is_array($token) && in_array($token[0], [T_WHITESPACE, T_DOC_COMMENT, T_COMMENT], true) && !($token[0] == T_COMMENT && $token[1] == static::EXPLICIT_TAG)) {
                continue;
            }
            // Process EXPLICIT_TAG (/*gettext-miner*/) -- jump to string extraction
            if ($token[0] == T_COMMENT && $token[1] == static::EXPLICIT_TAG) {
                $state = PHPParsingState::EXTRACT_STRING;
                continue;
            }
            // Run the token detection state machine
            switch ($state) {
                // Look for -> or :: operators
                case PHPParsingState::START:
                    if (is_array($token) && in_array($token[0], [T_DOUBLE_COLON, T_OBJECT_OPERATOR])) {
                        $state = PHPParsingState::FUNCTIONS_FOLLOWS;
                    }
                    break;
                // Look & check if function name matches the list
                case PHPParsingState::FUNCTIONS_FOLLOWS:
                    if (is_array($token) && $token[0] == T_STRING && isset($this->functions[$token[1]])) {
                        $state = PHPParsingState::FUNCTION_MATCHES;
                        $argumentPositionOffset = $this->functions[$token[1]] - 1;
                    } else {
                        $state = PHPParsingState::START; // restart
                    }
                    break;
                // Find for immediate opening (
                case PHPParsingState::FUNCTION_MATCHES:
                    if (!is_array($token) && $token == '(') {
                        $state = PHPParsingState::ARGUMENTS_FOLLOWS;
                    } else {
                        $state = PHPParsingState::START; // restart
                    }
                    break;
                // Seek argument on desired position
                case PHPParsingState::ARGUMENTS_FOLLOWS:
                    if ($argumentPositionOffset != 0) {
                        if (is_array($token) || $token != ',') {
                            continue 2;
                        } else {
                            $argumentPositionOffset -= 1;
                        }
                    } else {
                        // Process only T_CONSTANT_ENCAPSED_STRING - rest is ignored
                        if (is_array($token) && $token[0] === T_CONSTANT_ENCAPSED_STRING) {
                            $lastString = $token[1];
                            $lastLine = $token[2];
                            $state = PHPParsingState::ARGUMENT_MATCHING;
                        } else {
                            $state = PHPParsingState::START; // restart
                        }
                    }
                    break;
                // Match following text and store extracted string
                case PHPParsingState::ARGUMENT_MATCHING:
                    if (($token === ',' || $token === ')') && $argumentPositionOffset == 0) {
                        $messages[Helpers::removePHPStringWrap($lastString)][] = $path . ':' . $lastLine;
                        $state = PHPParsingState::START; // restart
                    } else {
                        $argumentPositionOffset = $argumentPositionOffset - 1; // not yet the right argument
                        if ($argumentPositionOffset < 0) {
                            $state = PHPParsingState::START; // not found
                        } else {
                            $state = PHPParsingState::ARGUMENTS_FOLLOWS; // continue searching
                        }
                    }
                    break;
                // Take the immediate next string and restart
                case PHPParsingState::EXTRACT_STRING:
                    if (is_array($token) && $token[0] === T_CONSTANT_ENCAPSED_STRING) {
                        $lastString = $token[1];
                        $lastLine = $token[2];
                        $messages[Helpers::removePHPStringWrap($lastString)][] = $path . ':' . $lastLine;
                        $state = PHPParsingState::START;
                    }
                    break;
                default:
                    throw new InvalidStateException(sprintf('Unexpected state of PHPFilter [%s] during traversal.', $state));
            }
        }
        return $messages;
    }


    #[Override]
    public function accepts(string $path): bool
    {
        return array_reduce($this->extensions, static fn(bool $carry, string $ext) => $carry || str_ends_with($path, $ext), false);
    }

    #[Override]
    public static function processConfig(array &$constructorArgs): void
    {
    }
}
