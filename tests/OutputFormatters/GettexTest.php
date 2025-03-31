<?php declare(strict_types=1);
namespace DrabekDigital\GettextMiner\Tests\OutputFormatters;

use DrabekDigital\GettextMiner\Detectors\SprintfPluralDetector;
use DrabekDigital\GettextMiner\Detectors\SymfonyTranslationPluralDetector;
use DrabekDigital\GettextMiner\OutputFormatters\Gettext;
use DrabekDigital\GettextMiner\Tests\Utils\ExistingNotExtractorClass;
use PHPUnit\Framework\TestCase;
use function array_slice;
use function explode;
use function implode;

class GettexTest extends TestCase
{
    public function testNoTranslationsAndEmptyString(): void
    {
        $formatter = new Gettext(new SprintfPluralDetector());

        $messages = [];

        $expected = <<<'EOD'
msgid ""
msgstr ""
"Content-Type: text/plain; charset=UTF-8\n"
"Plural-Forms: nplurals=2; plural=(n != 1);\n"

EOD;
        $this->assertEquals($expected, implode("\n", array_slice(explode("\n", $formatter->format($messages)), 2)));

        $messages2 = [
            '' => [
                'foo.php:12',
                'bar.php:99',
            ],
        ];
        $this->assertEquals($expected, implode("\n", array_slice(explode("\n", $formatter->format($messages2)), 2)));
    }

    public function testPluralsTranslations(): void
    {
        $formatter = new Gettext(new SprintfPluralDetector());

        $messages = [
            'You have %d mails in %s folder.' => ['project/app\temp.php:43']
        ];

        $expected = <<<'EOD'
msgid ""
msgstr ""
"Content-Type: text/plain; charset=UTF-8\n"
"Plural-Forms: nplurals=2; plural=(n != 1);\n"

#: project/app/temp.php:43
msgid "You have %d mails in %s folder."
msgid_plural "You have %d mails in %s folder."
msgstr[0] ""
msgstr[1] ""

EOD;

        $this->assertEquals($expected, implode("\n", array_slice(explode("\n", $formatter->format($messages)), 2)));
    }

    public function testAlternativePluralsTranslations(): void
    {
        $formatter = new Gettext(new SymfonyTranslationPluralDetector());

        $messages = [
            'You have %count% mails in %name% folder.' => ['project/app\core.php:12'],
        ];

        $expected = <<<'EOD'
msgid ""
msgstr ""
"Content-Type: text/plain; charset=UTF-8\n"
"Plural-Forms: nplurals=2; plural=(n != 1);\n"

#: project/app/core.php:12
msgid "You have %count% mails in %name% folder."
msgid_plural "You have %count% mails in %name% folder."
msgstr[0] ""
msgstr[1] ""

EOD;

        $this->assertEquals($expected, implode("\n", array_slice(explode("\n", $formatter->format($messages)), 2)));
    }

    public function testGettextReferences(): void
    {
        $formatter = new Gettext(new SprintfPluralDetector());

        $messages = [
            'Red' => [
                'project\app\core.php:10',
                'project/app core.php:1248',
            ]
        ];

        $expected = <<<'EOD'
msgid ""
msgstr ""
"Content-Type: text/plain; charset=UTF-8\n"
"Plural-Forms: nplurals=2; plural=(n != 1);\n"

#: project/app/core.php:10
#: ⁨project/app core.php:1248⁩
msgid "Red"
msgstr ""

EOD;

        $this->assertEquals($expected, implode("\n", array_slice(explode("\n", $formatter->format($messages)), 2)));
    }

    public function testNoGettextReferences(): void
    {
        $formatter = new Gettext(new SprintfPluralDetector(), false);

        $messages = [
            'Red' => [
                'project\app\core.php:10',
                'project/app core.php:1248',
            ]
        ];

        $expected = <<<'EOD'
msgid ""
msgstr ""
"Content-Type: text/plain; charset=UTF-8\n"
"Plural-Forms: nplurals=2; plural=(n != 1);\n"

msgid "Red"
msgstr ""

EOD;

        $this->assertEquals($expected, implode("\n", array_slice(explode("\n", $formatter->format($messages)), 2)));
    }

    public function testMultilineTranslations(): void
    {
        $formatter = new Gettext(new SprintfPluralDetector());

        $messages = [
            "Some long\ntranslation string" => ['project/app\core.php:12']
        ];

        $expected = <<<'EOD'
msgid ""
msgstr ""
"Content-Type: text/plain; charset=UTF-8\n"
"Plural-Forms: nplurals=2; plural=(n != 1);\n"

#: project/app/core.php:12
msgid "Some long\n"
"translation string"
msgstr ""

EOD;

        $this->assertEquals($expected, implode("\n", array_slice(explode("\n", $formatter->format($messages)), 2)));
    }

    public function testExtraMetadata(): void
    {
        $formatter = new Gettext(new SprintfPluralDetector(), true, ['Language' => 'cs_CZ']);

        $messages = [];

        $expected = <<<'EOD'
msgid ""
msgstr ""
"Content-Type: text/plain; charset=UTF-8\n"
"Plural-Forms: nplurals=2; plural=(n != 1);\n"
"Language: cs_CZ\n"

EOD;
        $this->assertEquals($expected, implode("\n", array_slice(explode("\n", $formatter->format($messages)), 2)));
    }

    public function testProcessConfig(): void
    {
        $config = [
            'detector' => ExistingNotExtractorClass::class,
        ];
        $this->expectException(\DrabekDigital\GettextMiner\Exceptions\InvalidConfigurationException::class);
        $this->expectExceptionMessage('class [DrabekDigital\GettextMiner\Tests\Utils\ExistingNotExtractorClass] is not an PluralDetector.');
        Gettext::processConfig($config);
    }
}
