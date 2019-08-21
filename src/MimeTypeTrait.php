<?php

declare(strict_types=1);

namespace Deviantintegral\Har;

trait MimeTypeTrait
{
    /**
     * MIME type of the response text (value of the Content-Type response
     * header). The charset attribute of the MIME type is included (if
     * available).
     *
     * @var string
     * @Serializer\Type("string")
     */
    protected $mimeType;

    /**
     * @return string
     */
    public function getMimeType(): string
    {
        return $this->mimeType;
    }

    /**
     * @param string $mimeType
     *
     * @return self
     */
    public function setMimeType(string $mimeType): self
    {
        $this->mimeType = $mimeType;

        return $this;
    }
}
