<?php declare(strict_types=1);
namespace DrabekDigital\GettextMiner\Utils;

use Exception;
use DrabekDigital\GettextMiner\Exceptions\InvalidConfigurationException;
use DrabekDigital\GettextMiner\Extractors\Extractor;
use DrabekDigital\GettextMiner\OutputFormatters\OutputFormatter;
use DrabekDigital\GettextMiner\Enums\TargetOptions;
use DrabekDigital\GettextMiner\Enums\TargetOutputOptions;
use Nette\Neon\Neon;
use Throwable;

class ConfigLoader
{
    private const string CONFIG_NAME = '.gettext-miner.neon';
    private const string DEFAULT_OUTPUT_FORMATTER = 'Gettext';

    public function load(string $path): bool|string
    {
        $configPath = $path . '/' . self::CONFIG_NAME;
        if (file_exists($configPath)) {
                return file_get_contents($configPath);
        } else {
            throw new InvalidConfigurationException(sprintf('Cannot find config in %s path.', $path));
        }
    }

    /**
     * @param string $configContent
     * @param string $path
     * @throws InvalidConfigurationException
     * @return Target[]
     */
    public function parse(string $configContent, string $path): array
    {
        $parsed = [];
        try {
            $configParsed = Neon::decode($configContent);
            if (!is_array($configParsed) || count($configParsed) === 0) {
                throw new InvalidConfigurationException(sprintf('The config file is empty or invalid.'));
            }
            foreach ($configParsed as $targetName => $targetOptions) {
                if (!is_array($targetOptions) || count($targetOptions) === 0) {
                    throw new InvalidConfigurationException(sprintf('Target [%s]: configuration is missing or invalid.', $targetName));
                }
                if (!is_string($targetName)) {
                    throw new InvalidConfigurationException(sprintf('Target [%s]: name is invalid.', $targetName));
                }
                if (!isset($targetOptions[TargetOptions::OUTPUT->value]) || !is_array($targetOptions[TargetOptions::OUTPUT->value])) {
                    throw new InvalidConfigurationException(sprintf('Target [%s]: output configuration is missing or invalid.', $targetName));
                }
                if (!isset($targetOptions[TargetOptions::OUTPUT->value][TargetOutputOptions::DESTINATION->value]) || !is_string($targetOptions[TargetOptions::OUTPUT->value][TargetOutputOptions::DESTINATION->value])) {
                    throw new InvalidConfigurationException(sprintf('Target [%s]: destination in output configuration is missing.', $targetName));
                }
                if (!isset($targetOptions[TargetOptions::SOURCES->value]) || !is_array($targetOptions[TargetOptions::SOURCES->value]) || !Helpers::isStringList($targetOptions[TargetOptions::SOURCES->value])) {
                    throw new InvalidConfigurationException(sprintf('Target [%s]: sources are missing or invalid.', $targetName));
                }
                if (isset($targetOptions[TargetOptions::FILES->value]) && !is_array($targetOptions[TargetOptions::FILES->value])) {
                    throw new InvalidConfigurationException(sprintf('Target [%s]: files are invalid for target.', $targetName));
                }
                if (!isset($targetOptions[TargetOptions::EXTRACTORS->value]) || !is_array($targetOptions[TargetOptions::EXTRACTORS->value])) {
                    throw new InvalidConfigurationException(sprintf('Target [%s]: extractors are missing or invalid.', $targetName));
                }
                $extractors = $this->prepareExtractors($targetOptions[TargetOptions::EXTRACTORS->value], $targetName);
                $output = $this->prepareOutput($targetOptions[TargetOptions::OUTPUT->value], $targetName);
                $target = new Target(
                    $targetName,
                    $targetOptions[TargetOptions::OUTPUT->value][TargetOutputOptions::DESTINATION->value],
                    $path,
                    $targetOptions[TargetOptions::SOURCES->value], // @phpstan-ignore-line
                    isset($targetOptions[TargetOptions::FILES->value]) && is_array($targetOptions[TargetOptions::FILES->value]) && Helpers::isStringList($targetOptions[TargetOptions::FILES->value]) ? $targetOptions[TargetOptions::FILES->value] : [], // @phpstan-ignore-line
                    $extractors,
                    $output,
                );
                $parsed[] = $target;
            }
            return $parsed;
        } catch (Exception $ex) {
            throw new InvalidConfigurationException($ex->getMessage(), 0, $ex);
        }
    }

    /**
     * @param array<mixed, mixed> $extractors
     * @param string $targetName
     * @throws InvalidConfigurationException
     * @return Extractor[]
     */
    private function prepareExtractors(array $extractors, string $targetName): array
    {
        $output = [];
        foreach ($extractors as $options) {
            if (!is_array($options) || count($options) === 0) {
                throw new InvalidConfigurationException(sprintf('Target [%s]: extractor configuration is missing or invalid.', $targetName));
            }
            if (!isset($options['extractor'])) {
                throw new InvalidConfigurationException(sprintf('Target [%s]: extractor type/class not provider.', $targetName));
            }
            $extractor = $options['extractor'];
            $className = (isset(Aliases::EXTRACTORS[$extractor])) ? Aliases::EXTRACTORS[$extractor] : $extractor;
            if (!is_string($className) || !class_exists($className)) {
                throw new InvalidConfigurationException(sprintf('Target [%s]: class [%s] could not been found.', $targetName, print_r($className, true)));
            }
            /** @var Extractor $instance */
            unset($options['extractor']);

            if (!is_a($className, Extractor::class, true)) {
                throw new InvalidConfigurationException(sprintf('Target [%s]: class [%s] is not an Extractor.', $targetName, $className));
            }

            try {
                $className::processConfig($options); // @phpstan-ignore-line
            } catch (Exception|Throwable $ex) { // @phpstan-ignore-line
                throw new InvalidConfigurationException(sprintf('Target [%s]: output config preparation failed with error [%s]', $targetName, $ex->getMessage()), $ex->getCode(), $ex);
            }

            try {
                $instance = new $className(...$options);
            } catch (Throwable $ex) { // @phpstan-ignore-line
                throw new InvalidConfigurationException(sprintf('Target [%s]: class [%s] could not been created due to an error: [%s]', $targetName, $className, $ex->getMessage()), 0, $ex);
            }
            $output[] = $instance;
        }
        return $output;
    }

    /**
     * @param array<mixed, mixed> $output
     * @param string $targetName
     * @throws InvalidConfigurationException
     * @return OutputFormatter
     */
    private function prepareOutput(array $output, string $targetName): OutputFormatter
    {
        $type = (isset($output['formatter'])) ? $output['formatter'] : self::DEFAULT_OUTPUT_FORMATTER;
        $className = (isset(Aliases::OUTPUT_FORMATTERS[$type])) ? Aliases::OUTPUT_FORMATTERS[$type] : $type;
        if (!is_string($className) || !class_exists($className)) {
            throw new InvalidConfigurationException(sprintf('Target [%s]: class [%s] could not been found.', $targetName, print_r($className, true)));
        }

        unset($output['formatter']);
        unset($output['destination']);

        if (!is_a($className, OutputFormatter::class, true)) {
            throw new InvalidConfigurationException(sprintf('Target [%s]: class [%s] is not an OutputFormatter.', $targetName, $className));
        }

        try {
            $className::processConfig($output); // @phpstan-ignore-line
        } catch (Exception|Throwable $ex) { // @phpstan-ignore-line
            throw new InvalidConfigurationException(sprintf('Target [%s]: output config preparation failed with error [%s]', $targetName, $ex->getMessage()), $ex->getCode(), $ex);
        }

        try {
            $instance = new $className(...$output);
        } catch (Throwable $ex) { // @phpstan-ignore-line
            throw new InvalidConfigurationException(sprintf('Target [%s]: class [%s] could not been created due to an error: [%s]', $targetName, $className, $ex->getMessage()), 0, $ex);
        }
        
        return $instance;
    }
}
