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
     * For example having unknown string to be wrapped in "" means that " needs escaping
     *
     * @param string $string
     * @param string $charlist
     * @return string
     */
    public static function escapeStringToBeWrapped(string $string, string $charlist = '"'): string
    {
        return addcslashes($string, $charlist);
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
     * Removes quotes (single, double) from given string and fix escaped quotes
     * 'Hello World' -> Hello World
     *
     * @param string|null $value
     * @return string
     */
    public static function removePHPStringWrap(?string $value)
    {
        if ($value === null) {
            return '';
        }
        $symbol = null;
        if (($value[0] === "'" && $value[strlen($value) - 1] === "'") || ($value[0] === '"' || $value[strlen($value) - 1] === '"')) {
            $symbol = $value[0];
            $value = substr($value, 1, -1);
        }
        if ($symbol === '\'') {
            $value = str_replace("\'", "'", $value);
        }
        if ($symbol === '"') {
            $value = str_replace('\"', '"', $value);
        }
        return $value;
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
