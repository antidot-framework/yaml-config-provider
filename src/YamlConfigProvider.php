<?php

declare(strict_types=1);

namespace Antidot\Yaml;

use Laminas\Config\Reader\Yaml;
use Laminas\ConfigAggregator\GlobTrait;
use RuntimeException;
use Symfony\Component\Yaml\Yaml as YamlParser;

class YamlConfigProvider
{
    use GlobTrait;

    private string $pattern;

    public function __construct(string $pattern)
    {
        $this->pattern = $pattern;
    }

    /**
     * Provide configuration.
     *
     * Globs the given files, and passes the result to ConfigFactory::fromFiles
     * for purposes of returning merged configuration.
     *
     * @return array
     */
    public function __invoke(): array
    {
        $files = $this->glob($this->pattern);

        return $this->fromFiles($files);
    }

    private function fromFiles(array $files): array
    {
        $config = [[]];
        foreach ($files as $file) {
            $config[] = $this->fromFile($file);
        }

        return array_replace_recursive(...$config) ?? [];
    }

    private function fromFile($filename): array
    {
        $filepath = $filename;
        if (!file_exists($filename)) {
            throw new RuntimeException(sprintf(
                'Filename "%s" cannot be found relative to the working directory',
                $filename
            ));
        }

        $pathinfo = pathinfo($filepath);

        if (!isset($pathinfo['extension'])) {
            throw new RuntimeException(sprintf(
                'Filename "%s" is missing an extension and cannot be auto-detected',
                $filename
            ));
        }

        $extension = strtolower($pathinfo['extension']);

        if (false === in_array($extension, ['yaml', 'yml'])) {
            throw new RuntimeException(sprintf(
                "File '%s' doesn't exist or not readable",
                $filename
            ));
        }

        $reader = new Yaml(static function ($config) {
            return YamlParser::parse($config);
        });

        return $reader->fromFile($filepath);
    }
}
