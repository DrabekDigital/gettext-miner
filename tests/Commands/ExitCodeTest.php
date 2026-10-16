<?php declare(strict_types=1);

namespace DrabekDigital\GettextMiner\Tests\Commands;

use DrabekDigital\GettextMiner\Miner;
use DrabekDigital\GettextMiner\OutputFormatters\ArrayFile;
use DrabekDigital\GettextMiner\Utils\Target;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Output\BufferedOutput;

class ExitCodeTest extends TestCase
{
    public function testMinerReportsFailedTarget(): void
    {
        $projectPath = (string) realpath(__DIR__ . '/../../docs/examples/example1');
        $okTarget = new Target('Ok', sys_get_temp_dir() . '/gettext-miner-ok.php', $projectPath, ['sources'], [], [], new ArrayFile());
        $failingTarget = new Target('Failing', $projectPath . '/destination', $projectPath, ['sources'], [], [], new ArrayFile());

        $this->assertTrue((new Miner(new BufferedOutput(), [$okTarget]))->run());
        $this->assertFalse((new Miner(new BufferedOutput(), [$failingTarget, $okTarget]))->run());
        @unlink(sys_get_temp_dir() . '/gettext-miner-ok.php');
    }

    public function testBinWithoutAutoloadExitsWithError(): void
    {
        $dir = sys_get_temp_dir() . '/gettext-miner-bin-' . uniqid();
        mkdir($dir . '/bin', recursive: true);
        copy(__DIR__ . '/../../bin/gettext-miner', $dir . '/bin/gettext-miner');

        $process = proc_open(
            [PHP_BINARY, $dir . '/bin/gettext-miner'],
            [1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
            $pipes,
            $dir
        );
        $this->assertIsResource($process);
        /** @var array<int, resource> $pipes */
        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        $exitCode = proc_close($process);

        unlink($dir . '/bin/gettext-miner');
        rmdir($dir . '/bin');
        rmdir($dir);

        $this->assertSame(1, $exitCode);
        $this->assertSame('', $stdout);
        $this->assertStringContainsString('Cannot find Composer autoload.php', (string) $stderr);
    }
}
