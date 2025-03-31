<?php declare(strict_types=1);
namespace DrabekDigital\GettextMiner\Tests\Extractors;

use DrabekDigital\GettextMiner\Extractors\SQL;
use PHPUnit\Framework\TestCase;

class SQLTest extends TestCase
{
    public function testAccepts(): void
    {
        $instance = new SQL();
        $this->assertFalse($instance->accepts('first.html'));
        $this->assertFalse($instance->accepts('example.latte'));
        $this->assertFalse($instance->accepts('second.phtml'));
        $this->assertFalse($instance->accepts('random.sqlm'));
        $this->assertTrue($instance->accepts('migration.sql'));
        $this->assertFalse($instance->accepts('microsoft.msql'));
    }

    public function testSimple(): void
    {
        $content = <<<'EOD'
INSERT INTO custom_enum (id, value) VALUES (1, ,/*_*/'First'/*_*/);
INSERT INTO custom_enum (id, value) VALUES (2, ,/*_*/'Second'/*_*/);
INSERT INTO custom_enum (id, value) VALUES (3, ,/*_*/'Just " placed'/*_*/);
INSERT INTO custom_enum (id, value) VALUES (3, ,/*_*/'Jack O''Neill'/*_*/);
INSERT INTO custom_enum (id, value) VALUES (3, ,/*_*/'Long - text with some, punctuation!'/*_*/);
EOD;

        $instance = new SQL();
        $output = $instance->extract('file.php', $content);
        $this->assertArrayHasKey('First', $output);
        $this->assertArrayHasKey('Second', $output);
        $this->assertArrayHasKey('Just " placed', $output);
        $this->assertArrayHasKey('Jack O\'Neill', $output);
        $this->assertArrayHasKey('Long - text with some, punctuation!', $output);
        $this->assertCount(5, $output);
    }

    public function testComplex(): void
    {
        $content = <<<'EOD'
/*_*/'Everything is "awesome".'/*_*/
/*_*/'X Y''Z A'/*_*/
/*_*/"The thing called ""thing"" creeped out."/*_*/
/*_*/"Jack O'neill"/*_*/
EOD;

        $instance = new SQL();

        $output = $instance->extract('file.php', $content);
        $this->assertArrayHasKey('Everything is "awesome".', $output);
        $this->assertArrayHasKey('X Y\'Z A', $output);
        $this->assertArrayHasKey('The thing called "thing" creeped out.', $output);
        $this->assertArrayHasKey('Jack O\'neill', $output);
        $this->assertCount(4, $output);

        $content = <<<'EOD'
INSERT INTO custom_enum (id, value) VALUES (1 ,/*_*/'Hello world'/*_*/), (2, /*_*/'Hugo'/*_*/);
EOD;
        $output = $instance->extract('temp.php', $content);
        $this->assertArrayHasKey('Hello world', $output);
        $this->assertArrayHasKey('Hugo', $output);
        $this->assertCount(2, $output);
    }
}
