<?php

declare(strict_types=1);

namespace Deviantintegral\Har\SharedFields;

use JMS\Serializer\Annotation as Serializer;

trait CommentTrait
{
    /**
     * @Serializer\Type("string")
     */
    protected ?string $comment = null;

    public function setComment(string $comment): self
    {
        $this->comment = $comment;

        return $this;
    }

    public function hasComment(): bool
    {
        return null !== $this->comment;
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }
}
