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
    /**
     * @var string
     */
    private $repositoryPath;

    /**
     * HarFileRepository constructor.
     *
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
        $hars = scandir($this->repositoryPath);
        if (!$hars) {
            return [];
        }

        foreach ($hars as $index => $har_file) {
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
        $contents = file_get_contents($path);

        if (!$contents) {
            throw new \RuntimeException(sprintf('%s was unable to be loaded', $path));
        }

        return $contents;
    }
}
