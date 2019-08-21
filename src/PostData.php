<?php

declare(strict_types=1);

namespace Deviantintegral\Har;

use Deviantintegral\Har\SharedFields\CommentTrait;
use Deviantintegral\Har\SharedFields\MimeTypeTrait;
use Deviantintegral\Har\SharedFields\TextTrait;
use JMS\Serializer\Annotation as Serializer;

/**
 * @see http://www.softwareishard.com/blog/har-12-spec/#postData
 */
final class PostData
{
    use CommentTrait;
    use MimeTypeTrait;
    use TextTrait;

    /**
     * List of posted parameters (in case of URL encoded parameters).
     *
     * @var \Deviantintegral\Har\Params[]
     * @Serializer\Type("array<Deviantintegral\Har\Params>")
     */
    private $params;

    /**
     * @return \Deviantintegral\Har\Params[]
     */
    public function getParams(): array
    {
        return $this->params;
    }

    /**
     * @param \Deviantintegral\Har\Params[] $params
     *
     * @return PostData
     */
    public function setParams(array $params): self
    {
        $this->params = $params;

        return $this;
    }
}
