<?php

declare(strict_types=1);

namespace Deviantintegral\Har;

use JMS\Serializer\Annotation as Serializer;

final class Page
{
    /**
     * Support the finest \DateTime precision we can.
     */
    const ISO_8601_MICROSECONDS = 'Y-m-d\TH:i:s.uO';

    /**
     * @var \DateTime
     * @Serializer\Type("DateTime")
     */
    private $startedDateTime;

    /**
     * @var string
     * @Serializer\Type("string")
     */
    private $id;

    /**
     * @var string
     * @Serializer\Type("string")
     */
    private $title;

    /**
     * @var \Deviantintegral\Har\PageTiming[]
     * @Serializer\Type("array<Deviantintegral\Har\PageTiming>")
     */
    private $pageTimings;

    /**
     * @var string
     * @Serializer\Type("string")
     */
    private $comment;

    /**
     * @return \DateTime
     */
    public function getStartedDateTime(): \DateTime
    {
        return $this->startedDateTime;
    }

    /**
     * @param \DateTime $startedDateTime
     *
     * @return Page
     */
    public function setStartedDateTime(\DateTime $startedDateTime): self
    {
        $this->startedDateTime = $startedDateTime;

        return $this;
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @param string $id
     *
     * @return Page
     */
    public function setId(string $id): self
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * @param string $title
     *
     * @return Page
     */
    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    /**
     * @return \Deviantintegral\Har\PageTiming[]
     */
    public function getPageTimings(): array
    {
        return $this->pageTimings;
    }

    /**
     * @param \Deviantintegral\Har\PageTiming[] $pageTimings
     *
     * @return Page
     */
    public function setPageTimings(array $pageTimings): self
    {
        $this->pageTimings = $pageTimings;

        return $this;
    }

    /**
     * @return string
     */
    public function getComment(): string
    {
        return $this->comment;
    }

    /**
     * @param string $comment
     *
     * @return Page
     */
    public function setComment(string $comment): self
    {
        $this->comment = $comment;

        return $this;
    }
}
