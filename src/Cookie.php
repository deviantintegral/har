<?php

declare(strict_types=1);

namespace Deviantintegral\Har;

use Deviantintegral\Har\SharedFields\CommentTrait;
use Deviantintegral\Har\SharedFields\ExpiresTrait;
use Deviantintegral\Har\SharedFields\NameValueTrait;
use JMS\Serializer\Annotation as Serializer;

/**
 * @see http://www.softwareishard.com/blog/har-12-spec/#cookies
 */
final class Cookie
{
    use CommentTrait;
    use ExpiresTrait;
    use NameValueTrait;

    /**
     * The name of the cookie.
     *
     * @Serializer\Type("string")
     */
    private string $cookie;

    /**
     * The path pertaining to the cookie.
     *
     * @Serializer\Type("string")
     */
    private string $path;

    /**
     * The host of the cookie.
     *
     * @Serializer\Type("string")
     */
    private string $domain;

    /**
     * Set to true if the cookie is HTTP only, false otherwise.
     *
     * @Serializer\Type("boolean")
     */
    private ?bool $httpOnly = null;

    /**
     * True if the cookie was transmitted over ssl, false otherwise.
     *
     * @Serializer\Type("boolean")
     */
    private ?bool $secure = null;

    public function getCookie(): string
    {
        return $this->cookie;
    }

    public function setCookie(string $cookie): self
    {
        $this->cookie = $cookie;

        return $this;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function setPath(string $path): self
    {
        $this->path = $path;

        return $this;
    }

    public function getDomain(): string
    {
        return $this->domain;
    }

    public function setDomain(string $domain): self
    {
        $this->domain = $domain;

        return $this;
    }

    public function hasHttpOnly(): bool
    {
        return null !== $this->httpOnly;
    }

    public function isHttpOnly(): ?bool
    {
        return $this->httpOnly;
    }

    public function setHttpOnly(bool $httpOnly): self
    {
        $this->httpOnly = $httpOnly;

        return $this;
    }

    public function hasSecure(): bool
    {
        return null !== $this->secure;
    }

    public function isSecure(): ?bool
    {
        return $this->secure;
    }

    public function setSecure(bool $secure): self
    {
        $this->secure = $secure;

        return $this;
    }
}
