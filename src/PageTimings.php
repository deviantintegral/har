<?php

declare(strict_types=1);

namespace Deviantintegral\Har;

use Deviantintegral\Har\SharedFields\CommentTrait;
use JMS\Serializer\Annotation as Serializer;

final class PageTimings
{
    use CommentTrait;

    /**
     * Content of the page loaded. Number of milliseconds since page load
     * started (page.startedDateTime). Use -1 if the timing does not apply to
     * the current request.
     */
    #[Serializer\Type('float')]
    private float $onContentLoad;

    /**
     * Page is loaded (onLoad event fired). Number of milliseconds since page
     * load started (page.startedDateTime). Use -1 if the timing does not apply
     * to the current request.
     */
    #[Serializer\Type('float')]
    private float $onLoad;

    public function getOnContentLoad(): float
    {
        return $this->onContentLoad;
    }

    public function setOnContentLoad(float $onContentLoad): self
    {
        $this->onContentLoad = $onContentLoad;

        return $this;
    }

    public function getOnLoad(): float
    {
        return $this->onLoad;
    }

    public function setOnLoad(float $onLoad): self
    {
        $this->onLoad = $onLoad;

        return $this;
    }
}
