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

    /**
     * Processes all targets (even when some of them fail).
     *
     * @return bool True if all targets have been processed successfully, false otherwise
     */
    public function run(bool $listFiles = false): bool
    {
        if (count($this->targets) === 0) {
            $this->output->writeln(sprintf('No targets configured, exiting.'));
            return true;
        }
        $success = true;
        foreach ($this->targets as $target) {
            $this->output->writeln(sprintf('Processing target [%s]...', $target->getName()));
            $success = $target->process($this->output, $listFiles) && $success;
        }
        return $success;
    }
}
