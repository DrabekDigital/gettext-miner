<?php declare(strict_types=1);
namespace DrabekDigital\GettextMiner\Tests\Extractors;

use DrabekDigital\GettextMiner\Exceptions\InvalidConfigurationException;
use DrabekDigital\GettextMiner\Extractors\Neon;
use PHPUnit\Framework\TestCase;

class NeonTest extends TestCase
{
    public function testAccepts(): void
    {
        $instance = new Neon([]);
        $this->assertTrue($instance->accepts('enums.neon'));
        $this->assertFalse($instance->accepts('template.latte'));
        $this->assertFalse($instance->accepts('template2.phtml'));
    }

    public function testInvalidCharactersInSelector(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $instance = new Neon(
            paths: [
                'enums.neon' => [
                    'parameters|(first)key',
                    'parameters|enum=',
                    'parameters|invalidchars%f',
                ]
            ]
        );
    }

    public function testComplex(): void
    {
        $instance = new Neon(
            paths: [
                'enums.neon2' => [
                    'parameters|hello-world',
                    'parameters|23fruit',
                    'parameters|about_page|peoples',
                ],
            ],
            extensions: ['.neon', '.neon2']
        );

        $config = <<<'EOD'
parameters:
    emailFrom: "foo@example.com"
    23fruit:
        kiwi: "Kiwi"
        banana: Banana
    about_page:
        peoples:
            - John Doe
            - Janex
    hello-world: "Hello World"
EOD;
        $output = $instance->extract('/enums.neon2', $config);
        $this->assertArrayHasKey('Kiwi', $output);
        $this->assertArrayHasKey('Banana', $output);
        $this->assertArrayHasKey('Hello World', $output);
        $this->assertArrayHasKey('John Doe', $output);
        $this->assertArrayHasKey('Janex', $output);
        $this->assertCount(5, $output);
    }

    public function testFilesMatching(): void
    {

        $document = <<<'EOD'
parameters:
    fruits:
        kiwi: "Kiwi"
        banana: Banana
EOD;

        $instance = new Neon(paths: [
                'App/Config/enums.neon' => [
                    'parameters|fruits',
                ]
            ]);

        $output = $instance->extract('C:\project\App\Config\enums.neon', $document, 'C:\project');
        $this->assertArrayHasKey('Kiwi', $output);
        $this->assertArrayHasKey('Banana', $output);
        $this->assertCount(2, $output);

        $document = <<<'EOD'
parameters:
    now: "Now"
EOD;

        $instance = new Neon(paths: [
            'App/Config/enums.neon' => [
                'parameters|fruits',
            ],
            'test.neon' => [
                'parameters|now',
            ]
        ]);

        $output = $instance->extract('/test.neon', $document);
        $this->assertArrayHasKey('Now', $output);
        $this->assertCount(1, $output);

        $output = $instance->extract('/test2.neon', $document);
        $this->assertCount(0, $output);
    }
}
