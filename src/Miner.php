<?php declare(strict_types=1);
namespace DrabekDigital\GettextMiner;

use DrabekDigital\GettextMiner\Exceptions\InvalidConfigurationException;
use DrabekDigital\GettextMiner\Utils\Target;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class that performs mining in all targets
 */
class Miner
{
    /** @var array<string, Target> Targets indexed by their name */
    private array $targets = [];

    /**
     * @param Target[] $targets
     */
    public function __construct(
        private readonly OutputInterface $output,
        array $targets = [],
    ) {
        foreach ($targets as $target) {
            $this->addTarget($target);
        }
    }

    /**
     * @throws InvalidConfigurationException when a target with the same name has been already added
     */
    public function addTarget(Target $target): void
    {
        if (isset($this->targets[$target->getName()])) {
            throw new InvalidConfigurationException(sprintf('The target [%s] is duplicate.', $target->getName()));
        }
        $this->targets[$target->getName()] = $target;
    }

    public function run(bool $listFiles = false): void
    {
        if (count($this->targets) === 0) {
            $this->output->writeln(sprintf('No targets configured, exiting.'));
            return;
        }
        foreach ($this->targets as $target) {
            $this->output->writeln(sprintf('Processing target [%s]...', $target->getName()));
            $target->process($this->output, $listFiles);
        }
    }
}
