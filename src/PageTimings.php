<?php

declare(strict_types=1);

namespace Deviantintegral\Har;

use JMS\Serializer\Annotation as Serializer;

final class PageTimings
{
    use CommentTrait;

    /**
     * Content of the page loaded. Number of milliseconds since page load
     * started (page.startedDateTime). Use -1 if the timing does not apply to
     * the current request.
     *
     * @var float
     * @Serializer\Type("float")
     */
    private $onContentLoad;

    /**
     * Page is loaded (onLoad event fired). Number of milliseconds since page
     * load started (page.startedDateTime). Use -1 if the timing does not apply
     * to the current request.
     *
     * @var float
     * @Serializer\Type("float")
     */
    private $onLoad;

    /**
     * @return float
     */
    public function getOnContentLoad(): float
    {
        return $this->onContentLoad;
    }

    /**
     * @param int $onContentLoad
     *
     * @return PageTimings
     */
    public function setOnContentLoad(int $onContentLoad): self
    {
        $this->onContentLoad = $onContentLoad;

        return $this;
    }

    /**
     * @return float
     */
    public function getOnLoad(): float
    {
        return $this->onLoad;
    }

    /**
     * @param float $onLoad
     *
     * @return PageTimings
     */
    public function setOnLoad(float $onLoad): self
    {
        $this->onLoad = $onLoad;

        return $this;
    }
}
