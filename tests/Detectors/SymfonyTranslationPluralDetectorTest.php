<?php declare(strict_types=1);
namespace DrabekDigital\GettextMiner\Tests\Detectors;

use DrabekDigital\GettextMiner\Detectors\SymfonyTranslationPluralDetector;
use PHPUnit\Framework\TestCase;

class SymfonyTranslationPluralDetectorTest extends TestCase
{
    public function testBasic(): void
    {
        $instance = new SymfonyTranslationPluralDetector();
        $this->assertTrue($instance->hasPluralPlaceholder('I have %count% items in a basket.'));
        $this->assertTrue($instance->hasPluralPlaceholder('In %count% seconds...'));
        $this->assertTrue($instance->hasPluralPlaceholder('In %cOUNT% seconds...'));
        $this->assertFalse($instance->hasPluralPlaceholder('Dear %name%,'));
        $this->assertTrue($instance->hasPluralPlaceholder('%count%'));
        $this->assertTrue($instance->hasPluralPlaceholder('There are %count% rabbits on %name% island.'));
        $this->assertFalse($instance->hasPluralPlaceholder('e is approximately %e%'));
        $this->assertFalse($instance->hasPluralPlaceholder('In \%count% seconds...'));
    }
}
