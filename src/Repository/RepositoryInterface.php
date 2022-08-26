<?php

declare(strict_types=1);

namespace Deviantintegral\Har\Repository;

use Deviantintegral\Har\Har;

/**
 * Interface for loading HAR objects.
 *
 * A repository could be a directory with files on disk, a database, or a remote
 * service.
 *
 * @see \Deviantintegral\Har\Repository\HarFileRepository
 */
interface RepositoryInterface
{
    /**
     * Load multiple HAR objects.
     *
     * @param array $ids (optional) An array of IDs to load. Omit to load all objects.
     *
     * @return \Generator yields a key / value where the key is the ID and the
     *                    value is a \Deviantintegral\Har\Har
     *
     * @throws \RuntimeException thrown if any object could not be loaded
     */
    public function loadMultiple(array $ids = []): \Generator;

    /**
     * Return an array of IDs contained in this repository.
     *
     * @return string[]
     *                  An array of IDs
     */
    public function getIds(): array;

    /**
     * Return a single fixture.
     *
     * @param string $id the ID of the fixture to load
     *
     * @throws \RuntimeException thrown if the object could not be loaded
     */
    public function load(string $id): Har;

    /**
     * Return the raw JSON from a HAR object.
     *
     * @throws \RuntimeException thrown if the object could not be loaded
     */
    public function loadJson(string $id): string;
}
