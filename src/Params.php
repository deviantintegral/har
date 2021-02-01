<?php

declare(strict_types=1);

namespace Deviantintegral\Har;

use Deviantintegral\Har\SharedFields\CommentTrait;
use Deviantintegral\Har\SharedFields\NameValueTrait;
use JMS\Serializer\Annotation as Serializer;

/**
 * @see http://www.softwareishard.com/blog/har-12-spec/#params
 */
final class Params
{
    use CommentTrait;
    use NameValueTrait;

    /**
     * Name of a posted file.
     *
     * @var string
     * @Serializer\Type("string")
     */
    private $fileName;

    /**
     * Content type of a posted file.
     *
     * @var string
     * @Serializer\Type("string")
     */
    private $contentType;

    public function hasFileName(): bool
    {
        return null === $this->fileName;
    }

    public function getFileName(): string
    {
        return $this->fileName;
    }

    /**
     * @return Params
     */
    public function setFileName(string $fileName): self
    {
        $this->fileName = $fileName;

        return $this;
    }

    public function hasContentType(): bool
    {
        return null === $this->contentType;
    }

    public function getContentType(): string
    {
        return $this->contentType;
    }

    /**
     * @return Params
     */
    public function setContentType(string $contentType): self
    {
        $this->contentType = $contentType;

        return $this;
    }
}
