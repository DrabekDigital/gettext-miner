<?php declare(strict_types=1);

namespace DrabekDigital\GettextMiner\Tests\Commands;

use DrabekDigital\GettextMiner\Commands\MineCommand;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\BufferedOutput;

class MineCommandTest extends TestCase
{
    private const string EXAMPLE_DIR = __DIR__ . '/../../docs/examples/example1';

    private string $originalCwd;

    protected function setUp(): void
    {
        $this->originalCwd = (string) getcwd();
    }

    protected function tearDown(): void
    {
        chdir($this->originalCwd); // the command changes working directory
    }

    /**
     * Runs the command with real CLI argument parsing (CommandTester would bypass it)
     *
     * @param list<string> $arguments
     */
    private function runCommand(array $arguments, BufferedOutput $output): int
    {
        $command = new MineCommand();
        return $command->run(new ArgvInput(['gettext-miner', ...$arguments]), $output);
    }

    public function testWithoutList(): void
    {
        $output = new BufferedOutput();
        $this->assertSame(Command::SUCCESS, $this->runCommand([self::EXAMPLE_DIR], $output));
        $this->assertStringNotContainsString('standalone.php', $output->fetch());
    }

    public function testListLongOption(): void
    {
        $output = new BufferedOutput();
        $this->assertSame(Command::SUCCESS, $this->runCommand([self::EXAMPLE_DIR, '--list'], $output));
        $this->assertStringContainsString('standalone.php', $output->fetch());
    }

    public function testListShortOption(): void
    {
        $output = new BufferedOutput();
        $this->assertSame(Command::SUCCESS, $this->runCommand(['-l', self::EXAMPLE_DIR], $output));
        $this->assertStringContainsString('standalone.php', $output->fetch());
    }
}
