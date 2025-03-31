<?php declare(strict_types=1);
namespace DrabekDigital\GettextMiner\Utils;

use DrabekDigital\GettextMiner\Extractors\Extractor;
use DrabekDigital\GettextMiner\OutputFormatters\OutputFormatter;
use Nette\Utils\Finder;
use Nette\Utils\FileInfo;
use SplFileInfo;
use Symfony\Component\Console\Output\OutputInterface;

class Target
{
    public function __construct(
        private readonly string $name,
        private readonly string $destination,
        private readonly string $projectPath,
        /** @var string[] */
        private array $sources,
        /** @var string[] */
        private array $files,
        /** @var Extractor[] */
        private array $extractors,
        private OutputFormatter $outputFormatter,
    ) {
        $this->sources = array_map(fn ($input) => $projectPath . '/' . $input, $sources);
        $this->files = array_map(fn ($input) => $projectPath . '/' . $input, $files);
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDestination(): string
    {
        return $this->destination;
    }

    /**
     * @return string[]
     */
    public function getSources(): array
    {
        return $this->sources;
    }

    /** @return string[] */
    public function getFiles(): array
    {
        return $this->files;
    }

    public function process(OutputInterface $output, bool $listFiles = false): void
    {
        /** @var array<string, array<string>> $messages */
        $messages = [];
        
        /**
         * Recursively go through all sources directories and files
         * @var string $path
         * @var FileInfo $fileInfo
         */
        foreach (Finder::findFiles('*')->from($this->sources) as $path => $fileInfo) {
            if ($listFiles) {
                $output->write(sprintf("%s:      ", str_replace($this->projectPath, '.', $path)));
            }
            if (!$fileInfo->isReadable()) {
                if (!$listFiles) {
                    $output->write(sprintf("%s:      ", str_replace($this->projectPath, '.', $path)));
                }
                $output->writeln('[ UNREADBABLE ]');
                continue;
            }
            /** @var Extractor $extractor */
            foreach ($this->extractors as $extractor) {
                if ($extractor->accepts($path)) {
                    if ($listFiles) {
                        $output->write(sprintf(' [ %s ] ', get_class($extractor)));
                    }
                    $fileContent = file_get_contents($path);
                    if (!is_string($fileContent)) {
                        if ($listFiles) {
                            $output->write('[ UNREADBABLE ]');
                        }
                        continue;
                    }
                    $filterData = $extractor->extract($path, $fileContent, $this->projectPath);
                    $messages = array_merge_recursive($messages, $filterData);
                }
            }
            if ($listFiles) {
                $output->write("\n");
            }
        }

        // Go through all explicitly listed files
        foreach ($this->files as $path) {
            if ($listFiles) {
                $output->write(sprintf("%s:      ", str_replace($this->projectPath, '.', $path)));
            }
            $fileInfo = new SplFileInfo($path);
            if (!$fileInfo->isReadable()) {
                if (!$listFiles) {
                    $output->write(sprintf("%s:      ", str_replace($this->projectPath, '.', $path)));
                }
                $output->writeln('[ UNREADBABLE ]');
                continue;
            }
            /** @var Extractor $extractor */
            foreach ($this->extractors as $extractor) {
                if ($extractor->accepts($path)) {
                    if ($listFiles) {
                        $output->write(sprintf(' [ %s ] ', get_class($extractor)));
                    }
                    $fileContent = file_get_contents($path);
                    if (!is_string($fileContent)) {
                        if ($listFiles) {
                            $output->write('[ UNREADBABLE ]');
                        }
                        continue;
                    }
                    $filterData = $extractor->extract($path, $fileContent, $this->projectPath);
                    $messages = array_merge_recursive($messages, $filterData);
                }
            }
        }
        if ($listFiles) {
            $output->write("\n");
        }

        // Post processing
        $messages = $this->toProjectPaths($messages); // @phpstan-ignore-line
        $formatted = $this->outputFormatter->format($messages);
        $output->write(sprintf('Saving extracted string template into: %s      ', $this->destination));
        if (@file_put_contents($this->destination, $formatted)) { // explicitly
            $output->write('[ OK ]');
        } else {
            $output->write('[ FAILED ] (permissions?)');
        }
        $output->writeln("\n");
    }
    /**
     * @param array<string, array<string>> $messages
     * @return array<string, array<string>>
     */
    private function toProjectPaths(array $messages): array
    {
        $output = [];
        foreach ($messages as $message => $occurrences) {
            $output[$message] = array_map(function ($input): string {
                return str_replace($this->projectPath, '.', $input);
            }, $occurrences);
        }
        return $output;
    }
}
