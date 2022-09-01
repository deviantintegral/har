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

    /**
     * @var string
     * @Serializer\Type("string")
     */
    private $version;

    /**
     * @var \Deviantintegral\Har\Creator
     * @Serializer\Type("Deviantintegral\Har\Creator")
     */
    private $creator;

    /**
     * @var \Deviantintegral\Har\Browser
     * @Serializer\Type("Deviantintegral\Har\Browser")
     */
    private $browser;

    /**
     * @var \Deviantintegral\Har\Page[]
     * @Serializer\Type("array<Deviantintegral\Har\Page>")
     */
    private $pages;

    /**
     * @var \Deviantintegral\Har\Entry[]
     * @Serializer\Type("array<integer, Deviantintegral\Har\Entry>")
     */
    private $entries;

    public function getVersion(): string
    {
        return $this->version;
    }

    public function setVersion(string $version): self
    {
        $this->version = $version;

        return $this;
    }

    /**
     * @return \Deviantintegral\Har\Creator
     */
    public function getCreator(): Creator
    {
        return $this->creator;
    }

    /**
     * @param \Deviantintegral\Har\Creator $creator
     */
    public function setCreator(Creator $creator): self
    {
        $this->creator = $creator;

        return $this;
    }

    /**
     * @return \Deviantintegral\Har\Browser
     */
    public function getBrowser(): Browser
    {
        return $this->browser;
    }

    /**
     * @param \Deviantintegral\Har\Browser $browser
     */
    public function setBrowser(Browser $browser): self
    {
        $this->browser = $browser;

        return $this;
    }

    /**
     * @return \Deviantintegral\Har\Page[]
     */
    public function getPages(): array
    {
        return $this->pages;
    }

    /**
     * @param \Deviantintegral\Har\Page[] $pages
     */
    public function setPages(array $pages): self
    {
        $this->pages = $pages;

        return $this;
    }

    /**
     * @return \Deviantintegral\Har\Entry[]
     */
    public function getEntries(): array
    {
        return $this->entries;
    }

    /**
     * @param \Deviantintegral\Har\Entry[] $entries
     */
    public function setEntries(array $entries): self
    {
        $this->entries = $entries;

        return $this;
    }
}
