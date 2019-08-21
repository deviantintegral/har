<?php

declare(strict_types=1);

namespace Deviantintegral\Har\SharedFields;

trait ExpiresTrait
{
    /**
     * Expiration time. (ISO 8601 - YYYY-MM-DDThh:mm:ss.sTZD, e.g.
     * 2009-07-24T19:20:30.123+02:00).
     *
     * @var \DateTime
     * @Serializer\Type("DateTime")
     */
    private $expires;

    /**
     * @return \DateTime
     */
    public function getExpires(): \DateTime
    {
        return $this->expires;
    }

    /**
     * @param \DateTime $expires
     *
     * @return self
     */
    public function setExpires(\DateTime $expires): self
    {
        $this->expires = $expires;

        return $this;
    }
}
