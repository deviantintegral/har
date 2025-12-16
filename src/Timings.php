<?php

declare(strict_types=1);

namespace Deviantintegral\Har;

use Deviantintegral\Har\SharedFields\CommentTrait;
use JMS\Serializer\Annotation as Serializer;

final class Timings
{
    use CommentTrait;

    /**
     * Time spent in a queue waiting for a network connection. Use -1 if the
     * timing does not apply to the current request.
     */
    #[Serializer\Type('float')]
    private float $blocked = -1.0;

    /**
     * DNS resolution time. The time required to resolve a host name. Use -1 if
     * the timing does not apply to the current request.
     */
    #[Serializer\Type('float')]
    private float $dns = -1.0;

    /**
     * Time required to create TCP connection. Use -1 if the timing does not
     * apply to the current request.
     */
    #[Serializer\Type('float')]
    private float $connect = -1.0;

    /**
     * Time required to send HTTP request to the server.
     */
    #[Serializer\Type('float')]
    private float $send;

    /**
     * Waiting for a response from the server.
     */
    #[Serializer\Type('float')]
    private float $wait;

    /**
     * Time required to read entire response from the server (or cache).
     */
    #[Serializer\Type('float')]
    private float $receive;

    /**
     * Time required for SSL/TLS negotiation. If this field is defined then the
     * time is also included in the connect field (to ensure backward
     * compatibility with HAR 1.1). Use -1 if the timing does not apply to the
     * current request.
     */
    #[Serializer\Type('float')]
    private float $ssl = -1.0;

    public function hasBlocked(): bool
    {
        return -1.0 !== $this->blocked;
    }

    public function getBlocked(): float
    {
        return $this->blocked;
    }

    public function setBlocked(float $blocked): self
    {
        $this->blocked = $blocked;

        return $this;
    }

    public function hasDns(): bool
    {
        return -1.0 !== $this->dns;
    }

    public function getDns(): float
    {
        return $this->dns;
    }

    public function setDns(float $dns): self
    {
        $this->dns = $dns;

        return $this;
    }

    public function hasConnect(): bool
    {
        return -1.0 !== $this->connect;
    }

    public function getConnect(): float
    {
        return $this->connect;
    }

    public function setConnect(float $connect): self
    {
        if ($connect < $this->getSsl()) {
            throw new \LogicException('Connect time must include SSL time');
        }
        $this->connect = $connect;

        return $this;
    }

    public function hasSsl(): bool
    {
        return -1.0 !== $this->ssl;
    }

    public function getSsl(): float
    {
        return $this->ssl;
    }

    public function setSsl(float $ssl): self
    {
        $this->ssl = $ssl;

        return $this;
    }

    public function getSend(): float
    {
        return $this->send;
    }

    public function setSend(float $send): self
    {
        if ($send < 0) {
            throw new \LogicException('Send must not be negative');
        }
        $this->send = $send;

        return $this;
    }

    public function getWait(): float
    {
        return $this->wait;
    }

    public function setWait(float $wait): self
    {
        if ($wait < 0) {
            throw new \LogicException('Wait must not be negative');
        }
        $this->wait = $wait;

        return $this;
    }

    public function getReceive(): float
    {
        return $this->receive;
    }

    public function setReceive(float $receive): self
    {
        if ($receive < 0) {
            throw new \LogicException('Receive must not be negative');
        }
        $this->receive = $receive;

        return $this;
    }
}
