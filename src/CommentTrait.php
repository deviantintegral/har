<?php

declare(strict_types=1);

namespace Deviantintegral\Har;

use JMS\Serializer\Annotation as Serializer;

trait CommentTrait
{
    /**
     * @var string
     * @Serializer\Type("string")
     */
    protected $comment;

    /**
     * @param string $comment
     *
     * @return self
     */
    public function setComment(string $comment): self
    {
        $this->comment = $comment;

        return $this;
    }

    /**
     * @return bool
     */
    public function hasComment(): bool
    {
        return null === $this->comment;
    }

    /**
     * @return string
     */
    public function getComment(): string
    {
        return $this->comment;
    }
}
