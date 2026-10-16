<?php declare(strict_types=1);

namespace DrabekDigital\GettextMiner\Tests;

use DrabekDigital\GettextMiner\Exceptions\InvalidConfigurationException;
use DrabekDigital\GettextMiner\Miner;
use DrabekDigital\GettextMiner\OutputFormatters\ArrayFile;
use DrabekDigital\GettextMiner\Utils\Target;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Output\BufferedOutput;

class MinerTest extends TestCase
{
    private function createTarget(string $name): Target
    {
        return new Target($name, 'destination.php', __DIR__, [], [], [], new ArrayFile());
    }

    public function testDuplicateTargetIsRejected(): void
    {
        $miner = new Miner(new BufferedOutput(), []);
        $miner->addTarget($this->createTarget('Module'));
        $miner->addTarget($this->createTarget('OtherModule'));

        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('The target [Module] is duplicate.');
        $miner->addTarget($this->createTarget('Module'));
    }

    public function testDuplicateTargetInConstructorIsRejected(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        new Miner(new BufferedOutput(), [$this->createTarget('Module'), $this->createTarget('Module')]);
    }

    public function testNoTargets(): void
    {
        $output = new BufferedOutput();
        (new Miner($output))->run();
        $this->assertSame("No targets configured, exiting.\n", $output->fetch());
    }
}
