<?php

declare(strict_types=1);

namespace Deviantintegral\Har;

use JMS\Serializer\Annotation as Serializer;

final class Timings
{
    use CommentTrait;

    /**
     * Time spent in a queue waiting for a network connection. Use -1 if the
     * timing does not apply to the current request.
     *
     * @var int
     * @Serializer\Type("integer"))
     */
    private $blocked = -1;

    /**
     * DNS resolution time. The time required to resolve a host name. Use -1 if
     * the timing does not apply to the current request.
     *
     * @var int
     * @Serializer\Type("integer")
     */
    private $dns = -1;

    /**
     * Time required to create TCP connection. Use -1 if the timing does not
     * apply to the current request.
     *
     * @var int
     * @Serializer\Type("integer")
     */
    private $connect = -1;

    /**
     * Time required to send HTTP request to the server.
     *
     * @var int
     * @Serializer\Type("integer")
     */
    private $send;

    /**
     * Waiting for a response from the server.
     *
     * @var int
     * @Serializer\Type("integer")
     */
    private $wait;

    /**
     * Time required to read entire response from the server (or cache).
     *
     * @var int
     * @Serializer\Type("integer")
     */
    private $receive;

    /**
     * Time required for SSL/TLS negotiation. If this field is defined then the
     * time is also included in the connect field (to ensure backward
     * compatibility with HAR 1.1). Use -1 if the timing does not apply to the
     * current request.
     *
     * @var int
     * @Serializer\Type("integer")
     */
    private $ssl = -1;

    /**
     * @return int
     */
    public function getBlocked(): int
    {
        return $this->blocked;
    }

    /**
     * @param int $blocked
     *
     * @return Timings
     */
    public function setBlocked(int $blocked): self
    {
        $this->blocked = $blocked;

        return $this;
    }

    /**
     * @return int
     */
    public function getDns()
    {
        return $this->dns;
    }

    /**
     * @param int $dns
     *
     * @return Timings
     */
    public function setDns($dns)
    {
        $this->dns = $dns;

        return $this;
    }

    /**
     * @return int
     */
    public function getConnect(): int
    {
        return $this->connect;
    }

    /**
     * @param int $connect
     *
     * @return Timings
     */
    public function setConnect(int $connect): self
    {
        if ($connect < $this->getSsl()) {
            throw new \LogicException('Connect time must include SSL time');
        }
        $this->connect = $connect;

        return $this;
    }

    /**
     * @return int
     */
    public function getSsl(): int
    {
        return $this->ssl;
    }

    /**
     * @param int $ssl
     *
     * @return Timings
     */
    public function setSsl(int $ssl): self
    {
        $this->ssl = $ssl;

        return $this;
    }

    /**
     * @return int
     */
    public function getSend(): int
    {
        return $this->send;
    }

    /**
     * @param int $send
     *
     * @return Timings
     */
    public function setSend(int $send): self
    {
        if ($send < 0) {
            throw new \LogicException('Send must not be negative');
        }
        $this->send = $send;

        return $this;
    }

    /**
     * @return int
     */
    public function getWait(): int
    {
        return $this->wait;
    }

    /**
     * @param int $wait
     *
     * @return Timings
     */
    public function setWait(int $wait): self
    {
        if ($wait < 0) {
            throw new \LogicException('Wait must not be negative');
        }
        $this->wait = $wait;

        return $this;
    }

    /**
     * @return int
     */
    public function getReceive(): int
    {
        return $this->receive;
    }

    /**
     * @param int $receive
     *
     * @return Timings
     */
    public function setReceive(int $receive): self
    {
        if ($receive < 0) {
            throw new \LogicException('Receive must not be negative');
        }
        $this->receive = $receive;

        return $this;
    }

}
