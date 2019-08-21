<?php

declare(strict_types=1);

namespace Deviantintegral\Har\SharedFields;

use Deviantintegral\Har\Content;

trait ContentTrait
{
    /**
     * content [object] - Details about the response body.
     *
     * @var \Deviantintegral\Har\Content
     * @Serializer\Type("Deviantintegral\Har\Content")
     */
    private $content;

    /**
     * @return \Deviantintegral\Har\Content
     */
    public function getContent(): \Deviantintegral\Har\Content
    {
        return $this->content;
    }

    /**
     * @param \Deviantintegral\Har\Content $content
     *
     * @return self
     */
    public function setContent(Content $content): self
    {
        $this->content = $content;

        return $this;
    }
}
