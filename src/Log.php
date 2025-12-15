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
}
