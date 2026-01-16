<?php

declare(strict_types=1);

namespace Deviantintegral\Har;

use Deviantintegral\Har\SharedFields\CommentTrait;
use Deviantintegral\Har\SharedFields\StartedDateTimeTrait;
use JMS\Serializer\Annotation as Serializer;

/**
 * An exported HTTP request.
 *
 * @see http://www.softwareishard.com/blog/har-12-spec/#entries
 */
final class Entry
{
    use CommentTrait;
    use StartedDateTimeTrait;

    /**
     * Reference to the parent page. Leave out this field if the application
     * does not support grouping by pages.
     */
    #[Serializer\Type('string')]
    private string $pageref;

    /**
     * Total elapsed time of the request in milliseconds. This is the sum of all
     * timings available in the timings object (i.e. not including -1 values).
     */
    #[Serializer\Type('float')]
    private float $time;

    /**
     * Detailed info about the request.
     */
    #[Serializer\Type("Deviantintegral\Har\Request")]
    private Request $request;

    /**
     * Detailed info about the response.
     */
    #[Serializer\Type("Deviantintegral\Har\Response")]
    private Response $response;

    /**
     * Info about cache usage.
     */
    #[Serializer\Type("Deviantintegral\Har\Cache")]
    private Cache $cache;

    #[Serializer\Type("Deviantintegral\Har\Timings")]
    private Timings $timings;

    #[Serializer\Type('string')]
    private string $serverIPAddress;

    #[Serializer\Type('string')]
    private string $connection;

    /**
     * Detailed info about the request.
     */
    #[Serializer\Type("Deviantintegral\Har\Initiator")]
    private ?Initiator $_initiator = null;

    public function getPageref(): string
    {
        return $this->pageref;
    }

    public function setPageref(string $pageref): self
    {
        $this->pageref = $pageref;

        return $this;
    }

    public function getTime(): float
    {
        return $this->time;
    }

    public function setTime(float $time): self
    {
        $this->time = $time;

        return $this;
    }

    public function getRequest(): Request
    {
        return $this->request;
    }

    public function setRequest(Request $request): self
    {
        $this->request = $request;

        return $this;
    }

    public function getResponse(): Response
    {
        return $this->response;
    }

    public function setResponse(Response $response): self
    {
        $this->response = $response;

        return $this;
    }

    public function getCache(): Cache
    {
        return $this->cache;
    }

    public function setCache(Cache $cache): self
    {
        $this->cache = $cache;

        return $this;
    }

    public function getTimings(): Timings
    {
        return $this->timings;
    }

    public function setTimings(Timings $timings): self
    {
        $this->timings = $timings;

        return $this;
    }

    public function getServerIPAddress(): string
    {
        return $this->serverIPAddress;
    }

    public function setServerIPAddress(string $serverIPAddress): self
    {
        $this->serverIPAddress = $serverIPAddress;

        return $this;
    }

    public function getConnection(): string
    {
        return $this->connection;
    }

    public function setConnection(string $connection): self
    {
        $this->connection = $connection;

        return $this;
    }

    public function getInitiator(): ?Initiator
    {
        return $this->_initiator;
    }

    public function hasInitiator(): bool
    {
        return null !== $this->_initiator;
    }

    public function setInitiator(Initiator $_initiator): self
    {
        $this->_initiator = $_initiator;

        return $this;
    }

    /**
     * Deep clone all object properties when cloning Entry.
     */
    public function __clone(): void
    {
        if (isset($this->request)) {
            $this->request = clone $this->request;
        }

        if (isset($this->response)) {
            $this->response = clone $this->response;
        }

        if (isset($this->cache)) {
            $this->cache = clone $this->cache;
        }

        if (isset($this->timings)) {
            $this->timings = clone $this->timings;
        }

        if (isset($this->_initiator)) {
            $this->_initiator = clone $this->_initiator;
        }
    }
}
