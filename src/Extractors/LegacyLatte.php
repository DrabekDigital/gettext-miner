<?php declare(strict_types=1);
namespace DrabekDigital\GettextMiner\Extractors;

use DrabekDigital\GettextMiner\Utils\Helpers;
use Override;

/**
 * Extractor for Latte templates.
 *
 * Notes:
 * - This uses regular expression for extraction of the macros and thus is usable before Latte 3.0,
 *   This however has some limitations and may not work in all cases, such as ob_end_flush() instead of content etc.
 * - Do not use HTML tags inside the pair macro (ideally do not use it at all)
 * - Whitespace in translations are ignored (same for newlines if nl2br and noEscape are not used)
 * - Beware that the filters are case sensitive by default
 * - You can add custom modifiers by passing them to the constructor, beware of the need for regex quoting (pg_quote)
 *   This is needed as the modifiers should have the power of matching modifiers with arguments such as mySpecialFilter[^|]+ to match mySpecialFilter: 10, 5
 *
 *
 * Supported macros:
 *  - {_'string'} - non pair macro
 *  - {_'string', $count} - non pair macro with argument
 *  - {_'string'|modifier} - non pair macro with modifier
 *  - {_'string', $count|modifier} - non pair macro with modifier
 *  - {_'string'|modifier1|modifier2} - non pair macro with multiple modifiers
 *  - {_'string', $count|modifier1|modifier2} - non pair macro with multiple modifiers
 *  - {_}string{/_} - pair macro
 *  - {_|modifier}string{/_} - pair macro with modifier
 *  - {_|modifier1|modifier2}string{/_} - pair macro with multiple modifiers
 *  - {$this->translate('string')} - function call macro
 *  - {$control->translate('string')} - function call macro
 *
 * Not supported:
 *  - {_, $count}{/_} - pair macro is not supported.
 *  - explicit domain specification
 *  - n:translate - not parsed
 *  - {!_'string'} - non pair macro with no escape
 */
class LegacyLatte implements Extractor
{
    private const string NONPAIR_REGEX = '#{(__PREFIXES__)\s*(__STRING_REGEX__)([^\|])*?(__MODIFIERS__)?}#u';
    private const string PAIR_REGEX = '#{(__PREFIXES__)(__MODIFIERS__)?}(.*?){/(__PREFIXES__)}#usm';
    private const string FUNCTION_REGEX = '#(\$this->translate|\$control->translate)\(\s*?(__STRING_REGEX__)\s*?(,|\))#usm';

    private const string STRING_REGEX = '(["\'])(\\\\?.)*?\3'; // Matches strings even with escaping

    /** @var string[] */
    protected array $prefixes = ['_'];

    /** @var string[] */
    protected array $modifiers = ['noescape', 'stripHtml', 'nl2br\|noescape', 'breakLines\|noescape'];

    /**
     * @param string[] $extraModifiers
     * @param string[] $extensions
     */
    public function __construct(
        private readonly array $extraModifiers = [],
        private readonly array $extensions = ['.latte'],
    ) {
    }

    #[Override]
    public function extract($path, $content, $projectDirectory = null): array
    {
        // Prepare regexex
        $prefixes = join('|', array_values($this->prefixes));
        $modifiersList = array_merge($this->modifiers, $this->extraModifiers);
        $modifiers = '\|(' .join('|', $modifiersList) . ')';

        $messages = [];

        // Non pair macros
        preg_match_all(
            str_replace('__STRING_REGEX__', self::STRING_REGEX, str_replace('__MODIFIERS__', $modifiers, str_replace('__PREFIXES__', $prefixes, self::NONPAIR_REGEX))),
            $content,
            $allMatches,
            PREG_OFFSET_CAPTURE
        );

        if ($allMatches && !empty($allMatches[2])) {
            foreach ($allMatches[2] as $match) {
                [$message, $offset] = $match;
                $messages[Helpers::removePHPStringWrap(Helpers::extractArgument($message))][] = $path . ':' . Helpers::offsetToLine($content, $offset);
            }
        }

        // Pair macros
        preg_match_all(
            str_replace('__MODIFIERS__', $modifiers, str_replace('__PREFIXES__', $prefixes, self::PAIR_REGEX)),
            $content,
            $allMatches,
            PREG_OFFSET_CAPTURE
        );
        if ($allMatches && !empty($allMatches[4])) {
            foreach ($allMatches[4] as $match) {
                list($message, $offset) = $match;
                $messages[Helpers::removePHPStringWrap($message)][] = $path . ':' . Helpers::offsetToLine($content, $offset);
            }
        }

        // Direct function calls
        preg_match_all(
            str_replace('__STRING_REGEX__', self::STRING_REGEX, self::FUNCTION_REGEX),
            $content,
            $allMatches,
            PREG_OFFSET_CAPTURE
        );
        if ($allMatches && !empty($allMatches[2])) {
            foreach ($allMatches[2] as $match) {
                list($message, $offset) = $match;
                $messages[Helpers::removePHPStringWrap($message)][] = $path . ':' . Helpers::offsetToLine($content, $offset);
            }
        }

        return $messages;
    }

    #[Override]
    public function accepts(string $path): bool
    {
        return array_reduce($this->extensions, static fn(bool $carry, string $ext) => $carry || str_ends_with($path, $ext), false);
    }

    public static function processConfig(array &$constructorArgs): void
    {
    }
}
