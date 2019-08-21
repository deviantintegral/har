<?php

declare(strict_types=1);

namespace Deviantintegral\Har;

use Deviantintegral\Har\SharedFields\CommentTrait;
use Deviantintegral\Har\SharedFields\ExpiresTrait;
use JMS\Serializer\Annotation as Serializer;

/**
 * @see http://www.softwareishard.com/blog/har-12-spec/#cache
 */
final class CacheState
{
    use ExpiresTrait;
    use CommentTrait;

    /**
     * lastAccess [string] - The last time the cache entry was opened.
     *
     * @var string
     * @Serializer\Type("string")
     */
    private $lastAccess;

    /**
     * eTag [string] - Etag.
     *
     * @var string
     * @Serializer\Type("string")
     */
    private $eTag;

    /**
     * hitCount [number] - The number of times the cache entry has been opened.
     *
     * @var int
     * @Serializer\Type("integer")
     */
    private $hitCount = 0;

    /**
     * @return string
     */
    public function getLastAccess(): string
    {
        return $this->lastAccess;
    }

    /**
     * @param string $lastAccess
     *
     * @return CacheState
     */
    public function setLastAccess(string $lastAccess): self
    {
        $this->lastAccess = $lastAccess;

        return $this;
    }

    /**
     * @return string
     */
    public function getETag(): string
    {
        return $this->eTag;
    }

    /**
     * @param string $eTag
     *
     * @return CacheState
     */
    public function setETag(string $eTag): self
    {
        $this->eTag = $eTag;

        return $this;
    }

    /**
     * @return int
     */
    public function getHitCount(): int
    {
        return $this->hitCount;
    }

    /**
     * @param int $hitCount
     *
     * @return CacheState
     */
    public function setHitCount(int $hitCount): self
    {
        $this->hitCount = $hitCount;

        return $this;
    }
}
