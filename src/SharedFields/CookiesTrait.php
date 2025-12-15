<?php

declare(strict_types=1);

namespace Deviantintegral\Har\SharedFields;

use JMS\Serializer\Annotation as Serializer;

trait CookiesTrait
{
    /**
     * cookies [array] - List of cookie objects.
     *
     * @var \Deviantintegral\Har\Cookie[]
     */
    #[Serializer\Type("array<Deviantintegral\Har\Cookie>")]
    private ?array $cookies = null;

    /**
     * @return \Deviantintegral\Har\Cookie[]
     */
    public function getCookies(): array
    {
        return $this->cookies ?? [];
    }

    /**
     * @param \Deviantintegral\Har\Cookie[] $cookies
     */
    public function setCookies(array $cookies): self
    {
        $this->cookies = $cookies;

        return $this;
    }
}
