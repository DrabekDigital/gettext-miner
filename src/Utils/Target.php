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
    /** @var list<string> Absolute paths of source directories */
    private array $sources;

    /** @var list<string> Absolute paths of explicitly listed files */
    private array $files;

    /**
     * @param string[] $sources Source directories relative to the project path
     * @param string[] $files Explicitly listed files relative to the project path
     * @param Extractor[] $extractors
     */
    public function __construct(
        private readonly string $name,
        private readonly string $destination,
        private readonly string $projectPath,
        array $sources,
        array $files,
        private array $extractors,
        private OutputFormatter $outputFormatter,
    ) {
        $this->sources = array_values(array_map(fn ($input) => $projectPath . '/' . $input, $sources));
        $this->files = array_values(array_map(fn ($input) => $projectPath . '/' . $input, $files));
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
     * @return list<string>
     */
    public function getSources(): array
    {
        return $this->sources;
    }

    /** @return list<string> */
    public function getFiles(): array
    {
        return $this->files;
    }

    /**
     * @return bool True if the extracted template has been successfully saved, false otherwise
     */
    public function process(OutputInterface $output, bool $listFiles = false): bool
    {
        /** @var array<array-key, array<string>> $messages */
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
                    $this->mergeMessages($messages, $filterData);
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
                    $this->mergeMessages($messages, $filterData);
                }
            }
        }
        if ($listFiles) {
            $output->write("\n");
        }

        // Post processing
        $messages = $this->toProjectPaths($messages);
        $formatted = $this->outputFormatter->format($messages);
        $output->write(sprintf('Saving extracted string template into: %s      ', $this->destination));
        $saved = @file_put_contents($this->destination, $formatted) !== false; // explicitly silenced, reported below
        $output->write($saved ? '[ OK ]' : '[ FAILED ] (permissions?)');
        $output->writeln("\n");
        return $saved;
    }
    /**
     * Merges extracted messages into the accumulator.
     *
     * Note: array_merge_recursive() cannot be used here, because PHP converts numeric string keys
     * (e.g. "404") to integers and array_merge_recursive() renumbers integer keys, which would
     * lose such messages.
     *
     * @param array<array-key, array<string>> $messages
     * @param array<array-key, array<string>> $extracted
     */
    private function mergeMessages(array &$messages, array $extracted): void
    {
        foreach ($extracted as $message => $occurrences) {
            foreach ($occurrences as $occurrence) {
                $messages[$message][] = $occurrence;
            }
        }
    }

    /**
     * @param array<array-key, array<string>> $messages
     * @return array<array-key, array<string>>
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
