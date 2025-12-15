<?php

declare(strict_types=1);

namespace Deviantintegral\Har\SharedFields;

use JMS\Serializer\Annotation as Serializer;

trait MimeTypeTrait
{
    /**
     * MIME type of the response text (value of the Content-Type response
     * header). The charset attribute of the MIME type is included (if
     * available).
     */
    #[Serializer\Type('string')]
    protected ?string $mimeType = null;

    public function getMimeType(): string
    {
        return $this->mimeType ?? '';
    }

    public function setMimeType(string $mimeType): self
    {
        $this->mimeType = $mimeType;

        return $this;
    }
}
