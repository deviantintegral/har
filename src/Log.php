<?php

declare(strict_types=1);

namespace Deviantintegral\Har;

use Deviantintegral\Har\SharedFields\CommentTrait;
use JMS\Serializer\Annotation as Serializer;

/**
 * Represents the root HTTP Archive node.
 *
 * @see http://www.softwareishard.com/blog/har-12-spec/#log
 */
class Log
{
    use CommentTrait;

    /**
     * Support the finest \DateTime precision we can.
     */
    public const ISO_8601_MICROSECONDS = 'Y-m-d\TH:i:s.uT';

    #[Serializer\Type('string')]
    private string $version;

    #[Serializer\Type("Deviantintegral\Har\Creator")]
    private Creator $creator;

    #[Serializer\Type("Deviantintegral\Har\Browser")]
    private Browser $browser;

    /**
     * @var Page[]
     */
    #[Serializer\Type("array<Deviantintegral\Har\Page>")]
    private array $pages;

    /**
     * @var Entry[]
     */
    #[Serializer\Type("array<integer, Deviantintegral\Har\Entry>")]
    private array $entries;

    public function getVersion(): string
    {
        return $this->version;
    }

    public function setVersion(string $version): self
    {
        $this->version = $version;

        return $this;
    }

    public function getCreator(): Creator
    {
        return $this->creator;
    }

    public function setCreator(Creator $creator): self
    {
        $this->creator = $creator;

        return $this;
    }

    public function getBrowser(): Browser
    {
        return $this->browser;
    }

    public function setBrowser(Browser $browser): self
    {
        $this->browser = $browser;

        return $this;
    }

    /**
     * @return Page[]
     */
    public function getPages(): array
    {
        return $this->pages;
    }

    /**
     * @param Page[] $pages
     */
    public function setPages(array $pages): self
    {
        $this->pages = $pages;

        return $this;
    }

    /**
     * @return Entry[]
     */
    public function getEntries(): array
    {
        return $this->entries;
    }

    /**
     * @param Entry[] $entries
     */
    public function setEntries(array $entries): self
    {
        $this->entries = $entries;

        return $this;
    }

    /**
     * Filter entries by URL pattern (regex).
     *
     * @param string $pattern A regular expression pattern to match against entry URLs
     *
     * @return Entry[] Entries matching the URL pattern
     */
    public function filterEntriesByUrlPattern(string $pattern): array
    {
        return array_values(array_filter(
            $this->entries,
            static fn (Entry $entry): bool => 1 === preg_match($pattern, (string) $entry->getRequest()->getUrl())
        ));
    }

    /**
     * Filter entries by HTTP method.
     *
     * @param string $method The HTTP method to filter by (GET, POST, etc.)
     *
     * @return Entry[] Entries matching the HTTP method
     */
    public function filterEntriesByMethod(string $method): array
    {
        $normalizedMethod = strtoupper($method);

        return array_values(array_filter(
            $this->entries,
            static fn (Entry $entry): bool => strtoupper($entry->getRequest()->getMethod()) === $normalizedMethod
        ));
    }

    /**
     * Filter entries by HTTP status code range.
     *
     * @param int $minStatus The minimum status code (inclusive)
     * @param int $maxStatus The maximum status code (inclusive)
     *
     * @return Entry[] Entries with status codes in the specified range
     */
    public function filterEntriesByStatus(int $minStatus, int $maxStatus): array
    {
        return array_values(array_filter(
            $this->entries,
            static fn (Entry $entry): bool => $entry->getResponse()->getStatus() >= $minStatus
                && $entry->getResponse()->getStatus() <= $maxStatus
        ));
    }

    /**
     * Deep clone all object properties when cloning Log.
     */
    public function __clone(): void
    {
        if (isset($this->creator)) {
            $this->creator = clone $this->creator;
        }

        if (isset($this->browser)) {
            $this->browser = clone $this->browser;
        }

        // Deep clone arrays of objects
        if (isset($this->pages)) {
            $this->pages = array_map(fn (Page $page) => clone $page, $this->pages);
        }

        if (isset($this->entries)) {
            $this->entries = array_map(fn (Entry $entry) => clone $entry, $this->entries);
        }
    }
}
