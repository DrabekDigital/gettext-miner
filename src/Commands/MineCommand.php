<?php declare(strict_types=1);
namespace DrabekDigital\GettextMiner\Commands;

use DrabekDigital\GettextMiner\Miner;
use DrabekDigital\GettextMiner\Exceptions\InvalidConfigurationException;
use DrabekDigital\GettextMiner\Utils\ConfigLoader;
use Override;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MineCommand extends Command
{
    private const string CURRENT_DIR = '.';

    #[Override]
    protected function configure(): void
    {
        $this
            ->setName('mine')
            ->setDescription('Mine all translation keys from given directory')
            ->addArgument(
                'path',
                InputArgument::OPTIONAL,
                'Please provide path to your project. Valid .gettext-miner.neon configuration is expected there.',
                self::CURRENT_DIR
            )
            ->addOption(
                'list',
                'l',
                InputArgument::OPTIONAL,
                'List all processed files',
                false
            );
        ;
    }

    #[Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $absolutePath = realpath($input->getArgument('path')); // @phpstan-ignore-line
        if ($absolutePath === false || !is_dir($absolutePath)) {
            $output->writeln('Directory not readable or does not exist.');
            return self::FAILURE;
        }
        $listFiles = $input->getOption('list');
        chdir($absolutePath);

        $configLoader = new ConfigLoader();
        try {
            $configContent = $configLoader->load($absolutePath);
            if (!is_string($configContent)) {
                $output->writeln('Cannot load config file.');
                return self::FAILURE;
            }
            $targets = $configLoader->parse($configContent, $absolutePath);
            $extractor = new Miner($output, []);
            foreach ($targets as $target) {
                $extractor->addTarget($target);
            }
            $extractor->run($listFiles); // @phpstan-ignore-line
        } catch (InvalidConfigurationException $ex) {
            $output->writeln($ex->getMessage());
            return self::FAILURE;
        }
        return self::SUCCESS;
    }
}
