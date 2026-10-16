<?php declare(strict_types=1);
namespace DrabekDigital\GettextMiner\Tests\OutputFormatters;

use DrabekDigital\GettextMiner\Enums\Indent;
use DrabekDigital\GettextMiner\OutputFormatters\ArrayFile;
use PHPUnit\Framework\TestCase;
use function file_put_contents;

class ArrayFileTest extends TestCase
{
    public function testBasicSpaces(): void
    {
        // test ArrayFile
        $outputFormatter = new ArrayFile('values', Indent::SPACES);
        $this->assertEquals(
            "<?php declare(strict_types=1);\n\$values = [\n    'Hello',\n    'World',\n];",
            $outputFormatter->format(['Hello' => [], 'World' => []])
        );
    }

    public function testBasicTabs(): void
    {
        // test ArrayFile
        $outputFormatter = new ArrayFile('values', Indent::TABS);
        $this->assertEquals(
            "<?php declare(strict_types=1);\n\$values = [\n\t'Hello',\n\t'World',\n];",
            $outputFormatter->format(['Hello' => [], 'World' => []])
        );
    }
    public function testWrapping(): void
    {
        // test ArrayFile
        $outputFormatter = new ArrayFile('values', Indent::TABS);
        $this->assertEquals(
            "<?php declare(strict_types=1);\n\$values = [\n\t'Hel\'lo',\n\t'New\\\\nline',\n\t'Wor\"ld',\n];",
            $outputFormatter->format(['Hel\'lo' => [], 'Wor"ld' => [], 'New\\nline' => []])
        );
    }

    public function testNumericMessages(): void
    {
        // PHP converts numeric string keys to integers, they still have to be in the output
        $outputFormatter = new ArrayFile('values', Indent::SPACES);
        $this->assertEquals(
            "<?php declare(strict_types=1);\n\$values = [\n    '2024',\n    '404',\n    'Hello',\n];",
            $outputFormatter->format(['404' => [], 'Hello' => [], '2024' => []])
        );
    }

    public function testBackslashes(): void
    {
        $outputFormatter = new ArrayFile('values', Indent::SPACES);
        $output = $outputFormatter->format(['C:\\temp' => [], 'Ends with \\' => []]);

        // The generated file has to be valid PHP containing the original strings
        $file = tempnam(sys_get_temp_dir(), 'gettext-miner');
        $this->assertIsString($file);
        file_put_contents($file, $output);
        $values = (static function (string $file): mixed {
            include $file;
            return get_defined_vars()['values'] ?? null;
        })($file);
        unlink($file);
        $this->assertSame(['C:\\temp', 'Ends with \\'], $values);
    }
}
