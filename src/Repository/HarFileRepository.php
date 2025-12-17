<?php

declare(strict_types=1);

namespace Deviantintegral\Har\Repository;

use Deviantintegral\Har\Har;
use Deviantintegral\Har\Serializer;

/**
 * Handles loading of HAR objects from disk.
 */
class HarFileRepository implements RepositoryInterface
{
    private string $repositoryPath;

    /**
     * @param string $repositoryPath the path to the repository on disk
     */
    public function __construct(string $repositoryPath)
    {
        $this->repositoryPath = $repositoryPath;
    }

    public function load(string $id): Har
    {
        $contents = $this->loadJson($id);

        return (new Serializer())->deserializeHar($contents);
    }

    /**
     * @param array<string> $ids
     */
    public function loadMultiple(array $ids = []): \Generator
    {
        if (empty($ids)) {
            $ids = $this->getIds();
        }

        foreach ($ids as $id) {
            yield $id => $this->load($id);
        }
    }

    public function getIds(): array
    {
        if (!is_dir($this->repositoryPath)) {
            // @infection-ignore-all: Equivalent mutation - without this return, scandir()
            // returns false for non-directories, caught by the if (!$hars) check below.
            return [];
        }

        $hars = scandir($this->repositoryPath);
        if (!$hars) {
            return [];
        }

        foreach ($hars as $index => $har_file) {
            // @infection-ignore-all: Equivalent mutation - changing < 4 to <= 4 has no effect
            // because any file with .har extension must be at least 5 chars ("x.har").
            // 4-char files would be filtered by length OR extension check either way.
            if (\strlen($har_file) < 4) {
                unset($hars[$index]);
                continue;
            }

            // Remove any file not ending in .har.
            if (false === strpos($har_file, '.har', \strlen($har_file) - \strlen('.har'))) {
                unset($hars[$index]);
                continue;
            }
        }

        sort($hars, \SORT_NATURAL);

        return $hars;
    }

    public function loadJson(string $id): string
    {
        $path = $this->repositoryPath.'/'.$id;
        if (!file_exists($path)) {
            throw new \RuntimeException(\sprintf('%s does not exist', $path));
        }

        $contents = file_get_contents($path);

        if (!$contents) {
            throw new \RuntimeException(\sprintf('%s was unable to be loaded', $path));
        }

        return $contents;
    }
}
