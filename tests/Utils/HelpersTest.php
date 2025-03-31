<?php declare(strict_types=1);

namespace DrabekDigital\GettextMiner\Tests\Utils;

use DrabekDigital\GettextMiner\Utils\Helpers;
use PHPUnit\Framework\TestCase;

class HelpersTest extends TestCase
{
    public function testGetRecursiveFromArrayByPath(): void
    {
        $array = [
            'a' => [
                'b' => [
                    'c' => 'd',
                ],
            ],
        ];

        $this->assertEquals(null, Helpers::getRecursiveFromArrayByPath($array, []));
        $this->assertEquals('d', Helpers::getRecursiveFromArrayByPath($array, ['a', 'b', 'c']));
        $this->assertEquals(null, Helpers::getRecursiveFromArrayByPath($array, ['a', 'b', 'c', 'd']));
        $this->assertEquals(null, Helpers::getRecursiveFromArrayByPath($array, ['a', 'b', 'd']));
        $this->assertEquals(['c' => 'd'], Helpers::getRecursiveFromArrayByPath($array, ['a', 'b']));
    }

    public function testConvertPathToForwardSlashes(): void
    {
        // Test with Windows path
        $this->assertEquals('C:/path/to/file', Helpers::convertPathToForwardSlashes('C:\path\to\file'));
    }

    public function testPreprocessGettextNewlines(): void
    {
        $this->assertEquals("foo\\n\"\n\"bar", Helpers::preprocessGettextNewlines("foo\nbar"));
        $this->assertEquals("simple line", Helpers::preprocessGettextNewlines("simple line"));
    }

    public function testEscapeStringToBeWrapped(): void
    {
        $this->assertEquals('Hello W\"orld', Helpers::escapeStringToBeWrapped('Hello W"orld'));
        $this->assertEquals("Hello W\'orld", Helpers::escapeStringToBeWrapped('Hello W\'orld', "'"));
    }

    public function testOffsetToLine(): void
    {
        $content = "Line 1\nLine 2\nLine 3\nLine 4";

        // Offset before the start of the string
        $this->assertEquals(1, Helpers::offsetToLine($content, -1));

        // Offset at the start of the string
        $this->assertEquals(1, Helpers::offsetToLine($content, 0));

        // Offset at the beginning of Line 2
        $this->assertEquals(2, Helpers::offsetToLine($content, 7));

        // Offset in the middle of Line 3
        $this->assertEquals(3, Helpers::offsetToLine($content, 14));

        // Offset at the start of Line 4
        $this->assertEquals(4, Helpers::offsetToLine($content, 21));

        // Offset beyond the last character
        $this->assertEquals(4, Helpers::offsetToLine($content, strlen($content) + 5));
    }

    public function testRemoveSQLStringWrap(): void
    {
        $this->assertEquals('Hello World', Helpers::removeSQLStringWrap("'Hello World'"));
        $this->assertEquals('Hello World', Helpers::removeSQLStringWrap('"Hello World"'));
        $this->assertEquals('Hello World', Helpers::removeSQLStringWrap('Hello World'));
        $this->assertEquals('Hello "World"', Helpers::removeSQLStringWrap('"Hello ""World"""'));
        $this->assertEquals('Hello \'World\'', Helpers::removeSQLStringWrap("'Hello ''World'''"));
    }

    public function testRemovePHPStringWrap(): void
    {
        $this->assertEquals('Hello string', Helpers::removePHPStringWrap('Hello string'));
        $this->assertEquals('Hello string', Helpers::removePHPStringWrap('"Hello string"'));
        $this->assertEquals('Hello string', Helpers::removePHPStringWrap("'Hello string'"));

        $this->assertEquals("Hello\nstring", Helpers::removePHPStringWrap("'Hello\nstring'"));
        $this->assertEquals("    Hello string    ", Helpers::removePHPStringWrap("'    Hello string    '"));

        $this->assertEquals('Hello o\'string', Helpers::removePHPStringWrap("'Hello o\'string'"));
        $this->assertEquals('Hello o"string', Helpers::removePHPStringWrap('"Hello o\"string"'));
    }
    public function testExtractArgument(): void
    {
        $this->assertEquals('"Hello string"', Helpers::extractArgument('"Hello string"'));
        $this->assertEquals("'Hello string'", Helpers::extractArgument("'Hello string'"));

        $this->assertEquals('"Hello %d string"', Helpers::extractArgument('"Hello %d string", $count'));
        $this->assertEquals("'Hello %d string'", Helpers::extractArgument("'Hello %d string', \$count"));

        $this->assertEquals('"Hello %d o\'clock"', Helpers::extractArgument('"Hello %d o\'clock", $count'));
        $this->assertEquals("'Hello %d o\"clock'", Helpers::extractArgument("'Hello %d o\"clock', \$count"));

        $this->assertEquals('$count', Helpers::extractArgument('"Hello %d o\'clock", $count', 2));
        $this->assertEquals("\$count", Helpers::extractArgument("'Hello %d o\"clock', \$count", 2));

        $this->assertEquals('$count', Helpers::extractArgument('"Hello %d o\'clock",$count', 2));
        $this->assertEquals("\$count", Helpers::extractArgument("'Hello %d o\"clock',\$count", 2));
    }
}
