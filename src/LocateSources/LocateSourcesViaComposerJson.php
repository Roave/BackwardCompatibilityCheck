<?php

declare(strict_types=1);

namespace Roave\BackwardCompatibility\LocateSources;

use Assert\Assert;
use Roave\BetterReflection\SourceLocator\Ast\Locator;
use Roave\BetterReflection\SourceLocator\Type\AggregateSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\DirectoriesSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\SingleFileSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\SourceLocator;
use function array_filter;
use function array_map;
use function array_merge;
use function array_values;
use function is_array;
use function is_dir;
use function is_file;
use function json_decode;
use function ltrim;
use function realpath;

final class LocateSourcesViaComposerJson implements LocateSources
{
    /** @var Locator */
    private $astLocator;

    public function __construct(Locator $astLocator)
    {
        $this->astLocator = $astLocator;
    }

    public function __invoke(string $installationPath) : SourceLocator
    {
        $realInstallationPath = realpath($installationPath);

        Assert::that($realInstallationPath)->string();

        $composerJsonPath = $realInstallationPath . '/composer.json';

        Assert::that($composerJsonPath)
              ->file()
              ->readable();

        $composerDefinitionString = file_get_contents($composerJsonPath);

        Assert::that($composerDefinitionString)->string();

        $composerDefinition = json_decode($composerDefinitionString, true);

        Assert::that($composerDefinition)->isArray();

        $autoloadDefinition = $composerDefinition['autoload'] ?? [];

        Assert::that($autoloadDefinition)->isArray();

        $prependInstallationPath = $this->prependInstallationPath($realInstallationPath);

        return new AggregateSourceLocator(array_merge(
            [
                new DirectoriesSourceLocator(
                    array_map(
                        $prependInstallationPath,
                        array_merge(
                            $this->psr0Directories($autoloadDefinition),
                            $this->psr4Directories($autoloadDefinition),
                            $this->classMapDirectories($autoloadDefinition, $installationPath)
                        )
                    ),
                    $this->astLocator
                ),
            ],
            array_map(
                function (string $path) : SourceLocator {
                    return new SingleFileSourceLocator($path, $this->astLocator);
                },
                array_map(
                    $prependInstallationPath,
                    $this->classMapFiles($autoloadDefinition, $installationPath)
                )
            ),
            array_map(
                function (string $path) : SourceLocator {
                    return new SingleFileSourceLocator($path, $this->astLocator);
                },
                array_map(
                    $prependInstallationPath,
                    $this->files($autoloadDefinition)
                )
            )
        ));
    }

    /**
     * @param mixed[] $autoloadDefinition
     *
     * @return string[]
     */
    private function classMapDirectories(array $autoloadDefinition, string $installationPath) : array
    {
        return array_values(array_filter(
            $autoloadDefinition['classmap'] ?? [],
            function (string $path) use ($installationPath) : bool {
                $filePath = ($this->prependInstallationPath($installationPath))($path);

                return is_dir($filePath);
            }
        ));
    }

    /**
     * @param mixed[] $autoloadDefinition
     *
     * @return string[]
     */
    private function classMapFiles(array $autoloadDefinition, string $installationPath) : array
    {
        return array_values(array_filter(
            $autoloadDefinition['classmap'] ?? [],
            function (string $path) use ($installationPath) : bool {
                $filePath = ($this->prependInstallationPath($installationPath))($path);

                return is_file($filePath);
            }
        ));
    }

    /**
     * @param mixed[] $autoloadDefinition
     *
     * @return string[]
     */
    private function files(array $autoloadDefinition) : array
    {
        return array_values($autoloadDefinition['files'] ?? []);
    }

    /**
     * @param mixed[] $autoloadDefinition
     *
     * @return string[]
     */
    private function psr0Directories(array $autoloadDefinition) : array
    {
        return array_merge(
            [],
            ...array_values(array_map([$this, 'stringToArray'], $autoloadDefinition['psr-0'] ?? []))
        );
    }

    /**
     * @param mixed[] $autoloadDefinition
     *
     * @return string[]
     */
    private function psr4Directories(array $autoloadDefinition) : array
    {
        return array_merge(
            [],
            ...array_values(array_map([$this, 'stringToArray'], $autoloadDefinition['psr-4'] ?? []))
        );
    }

    /** @return callable (string $path) : string */
    private function prependInstallationPath(string $installationPath) : callable
    {
        return function (string $path) use ($installationPath) : string {
            if (0 === strpos($path, './')) {
                return $installationPath . '/' . substr($path, 2);
            }

            return $installationPath . '/' . ltrim($path, '/');
        };
    }

    /**
     * @param string|string[] $entry
     *
     * @return string[]
     */
    private function stringToArray($entry) : array
    {
        if (is_array($entry)) {
            return array_values($entry);
        }

        return [$entry];
    }
}
