<?php declare(strict_types=1);

namespace DrabekDigital\GettextMiner\Tests\Utils;

use DrabekDigital\GettextMiner\Detectors\SprintfPluralDetector;
use DrabekDigital\GettextMiner\Enums\Indent;
use DrabekDigital\GettextMiner\Utils\ConfigLoader;
use DrabekDigital\GettextMiner\Utils\Target;
use PHPUnit\Framework\TestCase;
use DrabekDigital\GettextMiner\Exceptions\InvalidConfigurationException;

class ConfigLoaderTest extends TestCase
{

    public function testLoad(): void
    {
        $configLoader = new ConfigLoader();
        $this->assertEquals("FooBar:\n", $configLoader->load(__DIR__ . '/fixtures/'));
        
        $this->expectException(InvalidConfigurationException::class);
        $this->assertEquals("FooBar:\n", $configLoader->load(__DIR__ . '/fixtures/non-existent/'));
    }

    public function testParseInvalidTarget(): void
    {
        $configLoader = new ConfigLoader();
        $config = <<<END
        FooBar:
        END;
        $this->expectException(InvalidConfigurationException::class,);
        $this->expectExceptionMessage('Target [FooBar]: configuration is missing or invalid.');
        $configLoader->parse($config, '/project');
    }

    public function testParseInvalidTarget2(): void
    {
        $configLoader = new ConfigLoader();
        $config = <<<END
        FooBar: string
        END;
        $this->expectException(InvalidConfigurationException::class,);
        $this->expectExceptionMessage('Target [FooBar]: configuration is missing or invalid.');
        $configLoader->parse($config, '/project');
    }

    public function testParseInvalidTarget3(): void
    {
        $configLoader = new ConfigLoader();
        $config = "";
        $this->expectException(InvalidConfigurationException::class,);
        $this->expectExceptionMessage('The config file is empty or invalid.');
        $configLoader->parse($config, '/project');
    }

    public function testParseInvalidTarget4(): void
    {
        $configLoader = new ConfigLoader();
        $config = <<<END
        FooBar:
            output:
        END;
        $this->expectException(InvalidConfigurationException::class,);
        $this->expectExceptionMessage('Target [FooBar]: output configuration is missing or invalid.');
        $configLoader->parse($config, '/project');
    }
    public function testParseInvalidTarget5(): void
    {
        $configLoader = new ConfigLoader();
        $config = <<<END
        FooBar:
            output:
                asdf: asdf
        END;
        $this->expectException(InvalidConfigurationException::class,);
        $this->expectExceptionMessage('Target [FooBar]: destination in output configuration is missing.');
        $configLoader->parse($config, '/project');
    }
    public function testParseInvalidTarget6(): void
    {
        $configLoader = new ConfigLoader();
        $config = <<<END
        FooBar:
            output:
                destination: locales
        END;
        $this->expectException(InvalidConfigurationException::class,);
        $this->expectExceptionMessage('Target [FooBar]: sources are missing or invalid.');
        $configLoader->parse($config, '/project');
    }

    public function testParseInvalidTarget7(): void
    {
        $configLoader = new ConfigLoader();
        $config = <<<END
        FooBar:
            output:
                destination: locales
            sources: 
        END;
        $this->expectException(InvalidConfigurationException::class,);
        $this->expectExceptionMessage('Target [FooBar]: sources are missing or invalid.');
        $configLoader->parse($config, '/project');
    }

    public function testParseInvalidTarget8(): void
    {
        $configLoader = new ConfigLoader();
        $config = <<<END
        FooBar:
            output:
                destination: locales
            sources:
                - foo
        END;
        $this->expectException(InvalidConfigurationException::class,);
        $this->expectExceptionMessage('Target [FooBar]: extractors are missing or invalid.');
        $configLoader->parse($config, '/project');
    }
    public function testParseInvalidTarget9(): void
    {
        $configLoader = new ConfigLoader();
        $config = <<<END
        FooBar:
            output:
                destination: locales
            sources:
                - foo
            extractors:
        END;
        $this->expectException(InvalidConfigurationException::class,);
        $this->expectExceptionMessage('Target [FooBar]: extractors are missing or invalid.');
        $configLoader->parse($config, '/project');
    }

    public function testParseInvalidTarget10(): void
    {
        $configLoader = new ConfigLoader();
        $config = <<<END
        FooBar:
            output:
                destination: locales
            sources:
                - foo
            extractors:
                php: 
        END;
        $this->expectException(InvalidConfigurationException::class,);
        $this->expectExceptionMessage('Target [FooBar]: extractor configuration is missing or invalid.');
        $configLoader->parse($config, '/project');
    }

    public function testParseInvalidTarget11(): void
    {
        $configLoader = new ConfigLoader();
        $config = <<<END
        FooBar:
            output:
                destination: locales
            sources:
                - foo
            extractors:
                php: 
                    extractor: InvalidNonExistentClass
        END;
        $this->expectException(InvalidConfigurationException::class,);
        $this->expectExceptionMessage('Target [FooBar]: class [InvalidNonExistentClass] could not been found.');
        $configLoader->parse($config, '/project');
    }
    public function testParseInvalidTarget12(): void
    {
        $configLoader = new ConfigLoader();
        $config = <<<END
        FooBar:
            output:
                destination: locales
            sources:
                - foo
            files: asdf
            extractors:
                php: 
                    extractor: InvalidNonExistentClass
        END;
        $this->expectException(InvalidConfigurationException::class,);
        $this->expectExceptionMessage('Target [FooBar]: files are invalid for target.');
        $configLoader->parse($config, '/project');
    }

    public function testParseInvalidTarget13(): void
    {
        $configLoader = new ConfigLoader();
        $config = <<<END
        FooBar:
            output:
                destination: locales
            sources:
                - foo
            files: 
                - foo.php
            extractors:
                php: 
                    extractor: PHP
        END;
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessageMatches('/Target \[FooBar\]: class \[DrabekDigital\\\GettextMiner\\\OutputFormatters\\\Gettext\] could not been created due to an error: \[Too few arguments to function DrabekDigital\\\GettextMiner\\\OutputFormatters\\\Gettext::__construct\(\), 0 passed in .+ on line \d+ and at least 1 expected\]/');
        $configLoader->parse($config, '/project');
    }
    public function testParseInvalidTarget14(): void
    {
        $configLoader = new ConfigLoader();
        $config = <<<END
        FooBar:
            output:
                destination: locales
                detector: InvalidNonExistentClass
            sources:
                - foo
            files: 
                - foo.php
            extractors:
                php: 
                    extractor: PHP
        END;
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('Target [FooBar]: output config preparation failed with error [class [InvalidNonExistentClass] could not been found.]');
        $configLoader->parse($config, '/project');
    }

    public function testParseInvalidTarget15(): void
    {
        $configLoader = new ConfigLoader();
        $config = <<<END
        FooBar:
            output:
                destination: locales
                formatter: InvalidNonExistentClass 
            sources:
                - foo
            files: 
                - foo.php
            extractors:
                php: 
                    extractor: PHP
        END;
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('Target [FooBar]: class [InvalidNonExistentClass] could not been found.');
        $configLoader->parse($config, '/project');
    }

    public function testParseInvalidTarget16(): void
    {
        $configLoader = new ConfigLoader();
        $config = <<<END
        FooBar:
            output:
                destination: locales
                detector: SprintfPluralDetector
            sources:
                - foo
            files: 
                - foo.php
            extractors:
                php: 
                    extractor: CustomExtractor
        END;
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('Target [FooBar]: class [CustomExtractor] could not been found.');
        $configLoader->parse($config, '/project');
    }

    public function testParseInvalidTarget17(): void
    {
        $configLoader = new ConfigLoader();
        $config = <<<END
        FooBar:
            output:
                destination: locales
                detector: SprintfPluralDetector
            sources:
                - foo
            files: 
                - foo.php
            extractors:
                php: 
                    extractor: PHP
                    foo: bar
        END;
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('Target [FooBar]: class [DrabekDigital\GettextMiner\Extractors\PHP] could not been created due to an error: [Unknown named parameter $foo]');
        $configLoader->parse($config, '/project');
    }

    public function testParseInvalidTarget18(): void
    {
        $configLoader = new ConfigLoader();
        $config = <<<END
        FooBar:
            output:
                destination: locales
                detector: SprintfPluralDetector
            sources:
                - foo
            files: 
                - foo.php
            extractors:
                neon: 
                    extractor: DrabekDigital\GettextMiner\Extractors\Neon
        END;
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessageMatches('/Target \[FooBar\]: class \[DrabekDigital\\\GettextMiner\\\Extractors\\\Neon\] could not been created due to an error: \[Too few arguments to function DrabekDigital\\\GettextMiner\\\Extractors\\\Neon::__construct\(\), 0 passed in .+ on line \d+ and at least 1 expected\]/');
        $configLoader->parse($config, '/project');
    }

    public function testParseInvalidTarget19(): void
    {
        $configLoader = new ConfigLoader();
        $config = <<<END
        FooBar:
            output:
                destination: locales
            sources:
                - foo
            files:
                - foo.php
            extractors:
                php: 
                    extractor: \DrabekDigital\GettextMiner\Tests\Utils\ExistingNotExtractorClass
        END;
        $this->expectException(InvalidConfigurationException::class,);
        $this->expectExceptionMessage('Target [FooBar]: class [\DrabekDigital\GettextMiner\Tests\Utils\ExistingNotExtractorClass] is not an Extractor.');
        $configLoader->parse($config, '/project');
    }

    public function testParseInvalidTarget20(): void
    {
        $configLoader = new ConfigLoader();
        $config = <<<END
        FooBar:
            output:
                destination: locales
                formatter: \DrabekDigital\GettextMiner\Tests\Utils\ExistingNotExtractorClass
            sources:
                - foo
            files:
                - foo.php
            extractors:
                php: 
                    extractor: PHP
        END;
        $this->expectException(InvalidConfigurationException::class,);
        $this->expectExceptionMessage('Target [FooBar]: class [\DrabekDigital\GettextMiner\Tests\Utils\ExistingNotExtractorClass] is not an OutputFormatter.');
        $configLoader->parse($config, '/project');
    }

    public function testParseValid(): void
    {
        $configLoader = new ConfigLoader();
        $config = <<<END
        FooBar:
            output:
                destination: locales
                detector: SprintfPluralDetector
            sources:
                - foo
            files: 
                - foo.php
            extractors:
                php: 
                    extractor: PHP
        END;
        $targets = $configLoader->parse($config, '/project');
        $this->assertCount(1, $targets);
        $this->assertArrayHasKey(0, $targets);

        $target = $targets[0];
        $this->assertInstanceOf(Target::class, $target); // @phpstan-ignore-line
        $this->assertSame('FooBar', $target->getName());
        $this->assertSame('locales', $target->getDestination());
        $this->assertSame(['/project/foo'], $target->getSources());
        $this->assertSame(['/project/foo.php'], $target->getFiles());

        $extractors = $this->getPrivateProperty('extractors', $target);
        $this->assertIsArray($extractors);
        $this->assertCount(1, $extractors);
        $this->assertArrayHasKey(0, $extractors);
        $this->assertInstanceOf(\DrabekDigital\GettextMiner\Extractors\PHP::class, $extractors[0]);

        $output = $this->getPrivateProperty('outputFormatter', $target);
        $this->assertInstanceOf(\DrabekDigital\GettextMiner\OutputFormatters\Gettext::class, $output);
        $this->assertInstanceOf(SprintfPluralDetector::class, $this->getPrivateProperty('detector', $output));
    }

    public function testParseValid2(): void
    {
        $configLoader = new ConfigLoader();
        $config = <<<END
        FooBar:
            output:
                destination: locales
                detector: SprintfPluralDetector
            sources:
                - foo
            files: 
                - foo.php
            extractors:
                php: 
                    extractor: PHP
        FooBar2:
            output:
                destination: locales
                detector: SprintfPluralDetector
            sources:
                - foo
            files: 
                - foo.php
            extractors:
                php: 
                    extractor: PHP
        END;
        $targets = $configLoader->parse($config, '/project');
        $this->assertCount(2, $targets);
        $this->assertArrayHasKey(0, $targets);
        $this->assertArrayHasKey(1, $targets);
        $target1 = $targets[0];
        $this->assertSame('FooBar', $target1->getName());
        $target2 = $targets[1];
        $this->assertSame('FooBar2', $target2->getName());
        $this->assertNotSame($target1, $target2);
    }

    public function testParseValid3(): void
    {
        $configLoader = new ConfigLoader();
        $config = <<<END
        FooBar:
            output:
                destination: locales
                formatter: ArrayFile
                outputVariable: all
                indent: tabs
            sources:
                - foo
            files: 
                - foo.php
            extractors:
                php: 
                    extractor: PHP
        END;
        $targets = $configLoader->parse($config, '/project');
        $this->assertCount(1, $targets);
        $this->assertArrayHasKey(0, $targets);

        $target = $targets[0];
        $this->assertInstanceOf(Target::class, $target); // @phpstan-ignore-line
        $this->assertSame('FooBar', $target->getName());
        $this->assertSame('locales', $target->getDestination());
        $this->assertSame(['/project/foo'], $target->getSources());
        $this->assertSame(['/project/foo.php'], $target->getFiles());

        $extractors = $this->getPrivateProperty('extractors', $target);
        $this->assertIsArray($extractors);
        $this->assertCount(1, $extractors);
        $this->assertArrayHasKey(0, $extractors);
        $this->assertInstanceOf(\DrabekDigital\GettextMiner\Extractors\PHP::class, $extractors[0]);

        $output = $this->getPrivateProperty('outputFormatter', $target);
        $this->assertInstanceOf(\DrabekDigital\GettextMiner\OutputFormatters\ArrayFile::class, $output);
        $indent = $this->getPrivateProperty('indent', $output);
        $this->assertSame(Indent::TABS, $indent);
    }

    private function getPrivateProperty(string $property, object $object): mixed
    {
        $reflection = new \ReflectionClass(get_class($object));
        $property = $reflection->getProperty($property);
        $property->setAccessible(true);
        return $property->getValue($object);
    }
}
