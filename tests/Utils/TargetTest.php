<?php declare(strict_types=1);

namespace DrabekDigital\GettextMiner\Tests\Utils;

use DrabekDigital\GettextMiner\Extractors\LegacyLatte;
use DrabekDigital\GettextMiner\Extractors\Neon;
use DrabekDigital\GettextMiner\Extractors\PHP;
use DrabekDigital\GettextMiner\Extractors\SQL;
use DrabekDigital\GettextMiner\OutputFormatters\ArrayFile;
use DrabekDigital\GettextMiner\Utils\Target;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;

class TargetTest extends TestCase
{

    public function setUp(): void
    {
        unlink(__DIR__ . '/../../docs/examples/example1/destination/template.pot');
    }
    public function test(): void
    {
        $target = new Target(
            "ExampleModule",
            __DIR__ . '/../../docs/examples/example1/destination/template.pot',
            realpath(__DIR__ . '/../../docs/examples'), // @phpstan-ignore-line
            [
                '../../docs/examples/example1//sources/folder',
            ],
            [
                '../../docs/examples/example1//sources/standalone.php',
            ],
            [
                new PHP(
                    extraFunctions: [
                        'myCustomTranslate' => 1,
                    ]
                ),
                new LegacyLatte(
                    extraModifiers: [
                        'myCustomFilter'
                    ]
                ),
                new SQL(),
                new Neon(
                    paths: [
                        'sources/folder/config.neon' => [
                            'parameters|myEnum',
                        ]
                    ]
                )
            ],
            new ArrayFile()
        );

        $this->assertFileDoesNotExist(__DIR__ . '/../../docs/examples/example1/destination/template.pot');

        $output = new BufferedOutput(OutputInterface::VERBOSITY_NORMAL, true);
        $target->process($output, true);
        $finalOutput = $output->fetch();

        $currentDir = __DIR__ .'/';
        $expectedOutput = <<<EOF
        ./../../docs/examples/example1//sources/folder/file.php:       [ DrabekDigital\GettextMiner\Extractors\PHP ] 
        ./../../docs/examples/example1//sources/folder/file.latte:       [ DrabekDigital\GettextMiner\Extractors\LegacyLatte ] 
        ./../../docs/examples/example1//sources/folder/config.neon:       [ DrabekDigital\GettextMiner\Extractors\Neon ] 
        ./../../docs/examples/example1//sources/folder/sql.sql:       [ DrabekDigital\GettextMiner\Extractors\SQL ] 
        ./../../docs/examples/example1//sources/standalone.php:       [ DrabekDigital\GettextMiner\Extractors\PHP ] 
        Saving extracted string template into: $currentDir../../docs/examples/example1/destination/template.pot      [ OK ]
        EOF;
        $expectedOutput = $this->sortByLines(trim($expectedOutput));
        $finalOutput = $this->sortByLines(trim($finalOutput));
        $this->assertSame($expectedOutput, $finalOutput);
        $this->assertFileExists(__DIR__ . '/../../docs/examples/example1/destination/template.pot');

        $generatedFile = file_get_contents(__DIR__ . '/../../docs/examples/example1/destination/template.pot');

        $this->assertSame(<<<'EOF'
        <?php declare(strict_types=1);
        $messages = [
            'DB enum label',
            'Hello Hugo!',
            'My text',
            'Testing translation key',
            'Value',
            'You have %d mails.',
        ];
        EOF, $generatedFile);
    }

    private function sortByLines(string $input): string
    {
        $lines = explode("\n", $input);
        sort($lines);
        return implode("\n", $lines);
    }
}
