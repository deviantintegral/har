<?php
declare(strict_types=1);

namespace Deviantintegral\Har;

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
     * @return Creator
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
        return is_null($this->comment);
    }

    /**
     * @return string
     */
    public function getComment(): string
    {
        return $this->comment;
    }
}
