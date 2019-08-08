<?php

declare(strict_types=1);

namespace Deviantintegral\Har;

use JMS\Serializer\Annotation as Serializer;

/**
 * Defines a HAR creator.
 *
 * @see http://www.softwareishard.com/blog/har-12-spec/#creator
 */
class Creator
{
    /**
     * @var string
     * @Serializer\Type("string")
     */
    private $name;

    /**
     * @var string
     * @Serializer\Type("string")
     */
    private $version;

    /**
     * @var string
     * @Serializer\Type("string")
     */
    private $comment;

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     *
     * @return Creator
     */
    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return string
     */
    public function getVersion(): string
    {
        return $this->version;
    }

    /**
     * @param string $version
     *
     * @return Creator
     */
    public function setVersion(string $version): self
    {
        $this->version = $version;

        return $this;
    }

    /**
     * @return string
     */
    public function getComment(): string
    {
        return $this->comment;
    }

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
}
