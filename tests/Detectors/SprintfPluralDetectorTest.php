<?php declare(strict_types=1);
namespace DrabekDigital\GettextMiner\Tests\Detectors;

use DrabekDigital\GettextMiner\Detectors\SprintfPluralDetector;
use PHPUnit\Framework\TestCase;

class SprintfPluralDetectorTest extends TestCase
{
    public function testBasic(): void
    {
        $instance = new SprintfPluralDetector();
        $this->assertTrue($instance->hasPluralPlaceholder('I have %d items in a basket.'));
        $this->assertTrue($instance->hasPluralPlaceholder('In %d seconds...'));
        $this->assertFalse($instance->hasPluralPlaceholder('Dear %s,'));
        $this->assertTrue($instance->hasPluralPlaceholder('%decimal'));
        $this->assertTrue($instance->hasPluralPlaceholder('Range <%d-%d>.'));
        $this->assertTrue($instance->hasPluralPlaceholder('There are %d rabbits on %s island.'));
        $this->assertFalse($instance->hasPluralPlaceholder('e is approximately %f'));
    }
}
