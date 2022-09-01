<?php

declare(strict_types=1);

namespace Deviantintegral\Har;

use Deviantintegral\Har\SharedFields\CommentTrait;
use Deviantintegral\Har\SharedFields\NameTrait;
use JMS\Serializer\Annotation as Serializer;

/**
 * Defines a HAR creator.
 *
 * @see http://www.softwareishard.com/blog/har-12-spec/#creator
 */
class Creator
{
    use CommentTrait;
    use NameTrait;

    /**
     * @var string
     * @Serializer\Type("string")
     */
    private $version;

    public function getVersion(): string
    {
        return $this->version;
    }

    public function setVersion(string $version): self
    {
        $this->version = $version;

        return $this;
    }
}
