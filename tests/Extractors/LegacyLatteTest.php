<?php declare(strict_types=1);
namespace DrabekDigital\GettextMiner\Tests\Extractors;

use DrabekDigital\GettextMiner\Extractors\LegacyLatte;
use PHPUnit\Framework\TestCase;

class LegacyLatteTest extends TestCase
{
    public function testAcceptingFiles(): void
    {
        $filter = new LegacyLatte();
        $this->assertFalse($filter->accepts('my-file.phtml'), 'Files with Phtml extension should not be accepted.');
        $this->assertFalse($filter->accepts('my-file.html'), 'Files with Html extension should not be accepted.');
        $this->assertTrue($filter->accepts('my-file.latte'), 'Files with Latte extension should be accepted.');
    }

    public function testNonPairMacro(): void
    {
        $filter = new LegacyLatte();
        $this->assertArrayHasKey('Hi Hugo', $filter->extract('file.php', '{_"Hi Hugo"}'));
        $this->assertArrayHasKey('Hi Hugo %d', $filter->extract('file.php', '{_"Hi Hugo %d", 5}'));
        $this->assertArrayHasKey('Hi Hugo %d', $filter->extract('file.php', '{_"Hi Hugo %d", $count}'));
        $this->assertArrayHasKey('Hi O"Hugo', $filter->extract('file.php', '{_"Hi O\"Hugo"}'));

        $this->assertArrayHasKey('Hi Hugo', $filter->extract('file.php', "{_'Hi Hugo'}"));
        $this->assertArrayHasKey('Hi Hugo %d', $filter->extract('file.php', "{_'Hi Hugo %d', 5}"));
        $this->assertArrayHasKey('Hi Hugo %d', $filter->extract('file.php', "{_'Hi Hugo %d', \$count}"));
        $this->assertArrayHasKey("Hi O'Hugo", $filter->extract('file.php', "{_'Hi O\'Hugo'}"));

        $this->assertArrayHasKey('Hi Hugo', $filter->extract('file.php', '{_"Hi Hugo"|nl2br|noescape}'));
        $this->assertArrayHasKey('Hi Hugo %d', $filter->extract('file.php', '{_"Hi Hugo %d", $count|nl2br|noescape}'));
        $this->assertArrayHasKey('Hi Hugo', $filter->extract('file.php', '{_"Hi Hugo"|noescape}'));

        $this->assertArrayHasKey('m<sup>%d</sup>', $filter->extract('file.php', '{_"m<sup>%d</sup>", $redDwarf|noescape}'));
        $this->assertArrayHasKey('m<sup>3</sup>', $filter->extract('file.php', '{_"m<sup>3</sup>"|noescape}'));
    }

    public function testFilters(): void
    {
        $filter = new LegacyLatte();

        $this->assertArrayHasKey('Hi Hugo', $filter->extract('file.php', '{_"Hi Hugo"|stripHtml}'));
        $this->assertArrayHasKey('Hi Hugo %d', $filter->extract('file.php', '{_"Hi Hugo %d", $count|stripHtml}'));

        $this->assertArrayHasKey('Hi Hugo', $filter->extract('file.php', '{_"Hi Hugo"|noescape}'));
        $this->assertArrayHasKey('Hi Hugo %d', $filter->extract('file.php', '{_"Hi Hugo %d", $count|noescape}'));

        $this->assertArrayHasKey('Hi Hugo', $filter->extract('file.php', '{_"Hi Hugo"|breakLines|noescape}'));
        $this->assertArrayHasKey('Hi Hugo %d', $filter->extract('file.php', '{_"Hi Hugo %d", $count|breakLines|noescape}'));
    }

    public function testExtraFilters(): void
    {
        $filter = new LegacyLatte(
            ['myFilter', 'mySpecialFilter[^|]+', 'mySpecialFilter[^|]+\|noescape', 'myGreatFilter'],
        );

        $this->assertArrayHasKey('Hi Hugo', $filter->extract('file.php', '{_"Hi Hugo"|myFilter}'));
        $this->assertArrayHasKey('Hi Hugo %d', $filter->extract('file.php', '{_"Hi Hugo %d", $count|myFilter}'));

        $this->assertArrayHasKey('Hi Hugo', $filter->extract('file.php', '{_"Hi Hugo"|myGreatFilter}'));
        $this->assertArrayHasKey('Hi Hugo %d', $filter->extract('file.php', '{_"Hi Hugo %d", $count|myGreatFilter}'));

        $this->assertArrayHasKey('Hi Hugo', $filter->extract('file.php', '{_"Hi Hugo"|mySpecialFilter: 10, 5}'));
        $this->assertArrayHasKey('Hi Hugo', $filter->extract('file.php', '{_"Hi Hugo"|mySpecialFilter: 10, 5|noescape}'));
        $this->assertArrayHasKey('Hi Hugo %d', $filter->extract('file.php', '{_"Hi Hugo %d", $count|mySpecialFilter: 10, 5}'));
        $this->assertArrayHasKey('Hi Hugo %d', $filter->extract('file.php', '{_"Hi Hugo %d", $count|mySpecialFilter: 10, 5|noescape}'));
    }

    public function testPairMacro(): void
    {
        $filter = new LegacyLatte();
        $this->assertArrayHasKey('Hi Hugo', $filter->extract('file.php', '{_}Hi Hugo{/_}'));
        $this->assertArrayHasKey('Hi Hugo', $filter->extract('file.php', '{_|noescape}Hi Hugo{/_}'));
        $this->assertArrayHasKey('Hi Hugo', $filter->extract('file.php', '{_|nl2br|noescape}Hi Hugo{/_}'));

        $this->assertArrayHasKey("Hi\nHugo", $filter->extract('file.php', "{_}Hi\nHugo{/_}"));
    }

    public function testWeirdMacros(): void
    {
        $filter = new LegacyLatte();
        $this->assertArrayHasKey('The file you have tried to upload is over limit. The limit is {{maxFilesize}} MB.', $filter->extract('file.php', "{_}The file you have tried to upload is over limit. The limit is {{maxFilesize}} MB.{/_}"));
        $this->assertArrayHasKey('The file you have tried to upload is over limit. The limit is {{maxFilesize}} MB.', $filter->extract('file.php', "dictFileTooBig: {_'The file you have tried to upload is over limit. The limit is {{maxFilesize}} MB.'},"));
    }

    public function testFallback(): void
    {
        $filter = new LegacyLatte();
        $document = <<<'EOD'
<div class="row">
    {include #panel, 'label' => $this->translate('Basic data'), 'control' => 'propertyBasicInfo'}
    {include #panel, 'label' => $this->translate( 'Terrific O"Clock %d', 5), 'control' => 'propertyBasicInfo'}
    {include #panel, 'label' => $control->translate('Basic data 2'), 'control' => 'propertyBasicInfo'}
    {include #panel, 'label' => $control->translate( 'Terrific O"Clock %d 2', 5), 'control' => 'propertyBasicInfo'}
    {_}edit{/_}
</div>
EOD;
        $parsed = $filter->extract('file.latte', $document);
        $this->assertArrayHasKey('Basic data', $parsed);
        $this->assertArrayHasKey('Basic data 2', $parsed);
        $this->assertArrayHasKey('Terrific O"Clock %d', $parsed);
        $this->assertArrayHasKey('Terrific O"Clock %d 2', $parsed);
        $this->assertArrayHasKey('edit', $parsed);
    }

    public function testInvalidMacros(): void
    {
        $filter = new LegacyLatte();
        $this->assertCount(0, $filter->extract('file.php', '{_}Hi Hugo'));
        $this->assertCount(0, $filter->extract('file.php', '{_"Hi Hugo"'));
        $this->assertCount(0, $filter->extract('file.php', '_"Hi Hugo"}'));

        // Using < > must be ok
        $this->assertCount(1, $filter->extract('file.php', '{_"Orange > Apple"}'));
        $this->assertCount(1, $filter->extract('file.php', '{_}Orange > Apple{/_}'));
    }
}
