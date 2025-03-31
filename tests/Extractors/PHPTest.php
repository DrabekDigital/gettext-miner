<?php declare(strict_types=1);
namespace DrabekDigital\GettextMiner\Tests\Extractors;

use DrabekDigital\GettextMiner\Extractors\PHP;
use PHPUnit\Framework\TestCase;

class PHPTest extends TestCase
{
    public function testBasicFunctions(): void
    {
        $filter = new PHP();
        $this->assertFalse($filter->accepts('example.html'), 'Files with Html extension should not be accepted.');
        $this->assertTrue($filter->accepts('test.php'), 'Files with Php extension should be accepted.');

        $document = <<<'EOD'
<?php
$temp->translate('First');
$temp->_('Second');
self::translate('Third');
self::_('Fourth');
static::translate('Sixth');
static::_('Seventh');

$temp->translate('First %d', $count);
$temp->_('Second %d', $count);
self::translate('Third %d', $count);
self::_('Fourth %d', $count);
static::translate('Sixth %d', count);
static::_('Seventh %d', count);

$temp->translate($variable); // should be ignored
EOD;

        $output = $filter->extract('temp.php', $document);
        $this->assertArrayHasKey('First', $output);
        $this->assertArrayHasKey('Second', $output);
        $this->assertArrayHasKey('Third', $output);
        $this->assertArrayHasKey('Fourth', $output);
        $this->assertArrayHasKey('Sixth', $output);
        $this->assertArrayHasKey('Seventh', $output);
        $this->assertArrayHasKey('First %d', $output);
        $this->assertArrayHasKey('Second %d', $output);
        $this->assertArrayHasKey('Third %d', $output);
        $this->assertArrayHasKey('Fourth %d', $output);
        $this->assertArrayHasKey('Sixth %d', $output);
        $this->assertArrayHasKey('Seventh %d', $output);
        $this->assertCount(12, $output);
    }

    public function testOwnFunctions(): void
    {
        $filter = new PHP(
            functions: [
                'myFunction' => 1,
                'myFoo' => 2,
            ]
        );
        $document = <<<'EOD'
<?php
$temp->_('My string 0');    // should be ignored
$temp->translate('My string 1');    // should be ignored
$temp->myFunction('My string 2', 'Heureka');
$temp->myFoo(555, 'My string 3');

EOD;

        $output = $filter->extract('temp.php', $document);
        $this->assertArrayHasKey('My string 2', $output);
        $this->assertArrayHasKey('My string 3', $output);
        $this->assertCount(2, $output);
    }

    public function testExtraFunctions(): void
    {
        $filter = new PHP(
            extraFunctions: [
                'myFunction' => 1,
                'myFoo' => 2,
            ]
        );
        $document = <<<'EOD'
<?php
$temp->_('My string 0');
$temp->translate('My string 1');
$temp->myFunction('My string 2', 'Heureka');
$temp->myFoo(555, 'My string 3');

EOD;

        $output = $filter->extract('temp.php', $document);
        $this->assertArrayHasKey('My string 0', $output);
        $this->assertArrayHasKey('My string 1', $output);
        $this->assertArrayHasKey('My string 2', $output);
        $this->assertArrayHasKey('My string 3', $output);
        $this->assertCount(4, $output);
    }

    public function testWeirdInputs(): void
    {
        $filter = new PHP();
        $document = <<<'EOD'
<?php
$temp->translate('Full sentence with many words');
$temp->translate("new\nline");
$temp->translate("My o\"clock");
$temp->translate('My o\'clock 2');
$temp->translate('My o"clock 3');
$temp->translate("My o'clock 4");
$temp->translate('Ďábelský žluťoučký kůň pěl překásné ódy');

$temp->translate("Ignored" . $variable); // should be ignored
$temp->translate("Not ignored") . $variable; // should not be ignored

// comments insides etc.
EOD;

        $output = $filter->extract('temp.php', $document);
        $this->assertArrayHasKey('Full sentence with many words', $output);
        $this->assertArrayHasKey('new\nline', $output);
        $this->assertArrayHasKey("My o\"clock", $output);
        $this->assertArrayHasKey('My o\'clock 2', $output);
        $this->assertArrayHasKey('My o"clock 3', $output);
        $this->assertArrayHasKey("My o'clock 4", $output);
        $this->assertArrayHasKey('Ďábelský žluťoučký kůň pěl překásné ódy', $output);
        $this->assertArrayHasKey("Not ignored", $output);
        $this->assertCount(8, $output);
    }

    public function testVeryWeirdInputs(): void
    {
        $filter = new PHP();
        $document = <<<'EOD'
<?php
$temp->translate ('Full sentence with many words');
$temp->translate/*HA HUGO*/("new\nline");
$temp->translate(   "My o\"clock"  );
$temp->translate( // One line comment
    'My o\'clock 2'
);
$temp->translate(/*Another comment*/'My o"clock 3');
$temp->translate("My o'clock 4");
$temp->translate('Ďábelský žluťoučký kůň pěl překásné ódy');

$temp->translate('Something' ."Ignored"); // should be ignored
$temp->translate("Ignored $name is ignored"); // should be ignored

// comments insides etc.
EOD;

        $output = $filter->extract('temp.php', $document);
        $this->assertArrayHasKey('Full sentence with many words', $output);
        $this->assertArrayHasKey('new\nline', $output);
        $this->assertArrayHasKey("My o\"clock", $output);
        $this->assertArrayHasKey('My o\'clock 2', $output);
        $this->assertArrayHasKey('My o"clock 3', $output);
        $this->assertArrayHasKey("My o'clock 4", $output);
        $this->assertArrayHasKey('Ďábelský žluťoučký kůň pěl překásné ódy', $output);
        $this->assertCount(7, $output);
    }

    public function testExplicitExtract(): void
    {
        $filter = new PHP();
        $document = <<<'EOD'
<?php
weird_funct(/*gettext-miner*/ 'Full sentence with many words');
class Messages {
    const GENERAL_MESSAGE = /*gettext-miner*/ "Da Da";
}
EOD;
        $output = $filter->extract('temp.php', $document);
        $this->assertArrayHasKey('Full sentence with many words', $output);
        $this->assertArrayHasKey('Da Da', $output);
        $this->assertCount(2, $output);
    }

    public function testMultiline(): void
    {
        $filter = new PHP();
        $document = <<<'EOD'
<?php
$temp->translate ('Full sentence
 with many words');
EOD;
        $output = $filter->extract('temp.php', $document);
        $this->assertArrayHasKey("Full sentence\n with many words", $output);
        $this->assertCount(1, $output);
    }
}
