<?php

declare(strict_types=1);

namespace Deviantintegral\Har;

use JMS\Serializer\Annotation as Serializer;

/**
 * @see http://www.softwareishard.com/blog/har-12-spec/#params
 */
final class Params
{
    use NameValueTrait;
    use CommentTrait;

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

    /**
     * @return bool
     */
    public function hasFileName(): bool
    {
        return null === $this->fileName;
    }

    /**
     * @return string
     */
    public function getFileName(): string
    {
        return $this->fileName;
    }

    /**
     * @param string $fileName
     *
     * @return Params
     */
    public function setFileName(string $fileName): self
    {
        $this->fileName = $fileName;

        return $this;
    }

    /**
     * @return bool
     */
    public function hasContentType(): bool
    {
        return null === $this->contentType;
    }

    /**
     * @return string
     */
    public function getContentType(): string
    {
        return $this->contentType;
    }

    /**
     * @param string $contentType
     *
     * @return Params
     */
    public function setContentType(string $contentType): self
    {
        $this->contentType = $contentType;

        return $this;
    }
}
