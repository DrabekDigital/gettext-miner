<?php declare(strict_types=1);
namespace DrabekDigital\GettextMiner\Utils;

use function addcslashes;
use function str_replace;
use function str_split;
use function strlen;

final class Helpers
{
    /**
     * Converts backslashes to forward slashes in path
     *
     * @param string $path
     * @return string
     */
    public static function convertPathToForwardSlashes(string $path): string
    {
        return str_replace('\\', '/', $path);
    }

    /**
     * Preprocess newlines so they are visible (\n)
     *
     * @param string $content
     * @return string String with preprocessed newlines
     * @link https://www.gnu.org/software/gettext/manual/html_node/Normalizing.html
     *
     */
    public static function preprocessGettextNewlines(string $content): string
    {
        // The indent is needed to match the translation strings when using the content for translation
        return str_replace("\n", '\n"' . "\n\"", $content); // \n -> \\n"\n"
    }

    /**
     * Add slashes as escaping to prevent output breaking
     *
     * For example having unknown string to be wrapped in "" means that " needs escaping.
     * Backslash is always escaped as well, otherwise it would start an escape sequence
     * (e.g. C:\temp would contain a tab in gettext or a string ending with \ would break PHP output).
     *
     * @param string $string
     * @param string $charlist Characters to be escaped in addition to the backslash
     * @return string
     */
    public static function escapeStringToBeWrapped(string $string, string $charlist = '"'): string
    {
        return addcslashes($string, '\\' . $charlist);
    }

    /**
     * Computes and returns exact line number within given content
     *
     * @param string $content String content
     * @param int $offset Character offset
     * @return int Line number
     */
    public static function offsetToLine(string $content, int $offset): int
    {
        if ($offset < 1) {
            return 1;
        }
        [$before] = str_split($content, $offset); // Truncate at the offset
        return strlen($before) - strlen(str_replace("\n", "", $before)) + 1; // Count newlines in previous text
    }

    /**
     * Remove quotes (single/double) and fix potential escaping inside the string.
     *
     * @param string|null $value
     * @return string
     */
    public static function removeSQLStringWrap(?string $value): string
    {
        if ($value === null) {
            return '';
        }
        if (($value[0] === '\'' && $value[strlen($value) - 1] === '\'') || ($value[0] === '"' && $value[strlen($value) - 1] === '"')) {
            $symbol = $value[0];
            $value = substr($value, 1, -1);
        }
        if (isset($symbol)) {
            $value = str_replace($symbol . $symbol, $symbol, $value);
        }
        return $value;
    }

    /**
     * Removes quotes (single, double) from given PHP string literal and decodes its escape sequences
     * the same way PHP does:
     * 'Hello World' -> Hello World
     * 'It\'s' -> It's
     * "Line\nbreak" -> Line<newline>break
     *
     * Values not wrapped in matching quotes are returned unchanged.
     *
     * @param string|null $value
     * @return string
     * @link https://www.php.net/manual/en/language.types.string.php
     */
    public static function removePHPStringWrap(?string $value): string
    {
        if ($value === null) {
            return '';
        }
        $length = strlen($value);
        if ($length < 2 || $value[0] !== $value[$length - 1] || ($value[0] !== "'" && $value[0] !== '"')) {
            return $value;
        }
        $symbol = $value[0];
        $value = substr($value, 1, -1);

        if ($symbol === "'") {
            // Only \' and \\ are escape sequences in single-quoted strings
            return preg_replace('#\\\\([\\\\\'])#', '$1', $value) ?? $value;
        }

        return self::decodeDoubleQuotedEscapes($value);
    }

    /**
     * Decodes escape sequences of PHP double-quoted string, unknown sequences are kept as they are (as in PHP).
     */
    private static function decodeDoubleQuotedEscapes(string $value): string
    {
        return preg_replace_callback(
            '#\\\\(?:([nrtvef\\\\$"])|([0-7]{1,3})|x([0-9A-Fa-f]{1,2})|u\{([0-9A-Fa-f]+)\})#',
            self::decodeEscapeSequence(...),
            $value
        ) ?? $value;
    }

    /**
     * @param array<string> $match
     */
    private static function decodeEscapeSequence(array $match): string
    {
        if (($match[1] ?? '') !== '') {
            return match ($match[1]) {
                'n' => "\n",
                'r' => "\r",
                't' => "\t",
                'v' => "\v",
                'e' => "\e",
                'f' => "\f",
                default => $match[1], // \\, \$ and \"
            };
        }
        if (($match[2] ?? '') !== '') {
            return chr((int) octdec($match[2]) & 0xFF);
        }
        if (($match[3] ?? '') !== '') {
            return chr((int) hexdec($match[3]));
        }
        $codepoint = (int) hexdec($match[4] ?? '');
        if ($codepoint > 0x10FFFF || ($codepoint >= 0xD800 && $codepoint <= 0xDFFF)) {
            return $match[0]; // invalid codepoint, keep as is
        }
        return mb_chr($codepoint, 'UTF-8');
    }

    /**
     * @param array<string, mixed|array<mixed>> $input
     * @param array<string> $path
     * @return mixed
     */
    public static function getRecursiveFromArrayByPath(array $input, array $path): mixed
    {
        if (count($path) === 0) {
            return null;
        }

        foreach ($path as $key) {
            if (is_array($input) && array_key_exists($key, $input)) {
                $input = $input[$key];
            } else {
                return null;
            }
        }

        return $input;
    }

    /**
     * Extract first (or any other positional) argument from given Latte macro
     * 'Hello World %d', $count -> 'Hello World %d'
     *
     * @param string|null $value
     * @param int $position
     * @return string|null
     */
    public static function extractArgument(?string $value, int $position = 1)
    {
        if ($value === null) {
            return null;
        }
        $code = '<?php ' . $value . ' ?>';
        $tokens = token_get_all($code);
        $arg = 0;
        $curr = 0;
        while ($arg != $position) {
            $curr += 1;
            if (!isset($tokens[$curr])) {
                return null;
            }
            // Skip tokens without type and also whitespace
            if ($tokens[$curr][0] == T_WHITESPACE || !is_array($tokens[$curr])) {
                continue;
            }
            if ($arg != $position) {
                $arg += 1;
            }
        }
        if (isset($tokens[$curr][1])) {
            return $tokens[$curr][1];
        }
        return null;
    }

    /**
     * Returns true iff the given array is a list of strings (array<int, string>, string[], array<string>)
     * @param array<mixed> $input
     * @return bool
     */
    public static function isStringList(array $input): bool
    {
        foreach ($input as $key => $item) {
            if (!is_string($item) || !is_int($key)) {
                return false;
            }
        }
        return true;
    }
}
