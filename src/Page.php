<?php

declare(strict_types=1);

namespace Deviantintegral\Har;

use Deviantintegral\Har\SharedFields\CommentTrait;
use Deviantintegral\Har\SharedFields\StartedDateTimeTrait;
use JMS\Serializer\Annotation as Serializer;

final class Page
{
    use CommentTrait;
    use StartedDateTimeTrait;

    #[Serializer\Type('string')]
    private string $id;

    #[Serializer\Type('string')]
    private string $title;

    #[Serializer\Type("Deviantintegral\Har\PageTimings")]
    private PageTimings $pageTimings;

    public function getId(): string
    {
        return $this->id;
    }

    public function setId(string $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getPageTimings(): PageTimings
    {
        return $this->pageTimings;
    }

    public function setPageTimings(PageTimings $pageTimings): self
    {
        $this->pageTimings = $pageTimings;

        return $this;
    }
}
