<?php

declare(strict_types=1);

namespace Deviantintegral\Har\SharedFields;

use Deviantintegral\NullDateTime\DateTimeFormatInterface;
use JMS\Serializer\Annotation as Serializer;

trait ExpiresTrait
{
    /**
     * Expiration time. (ISO 8601 - YYYY-MM-DDThh:mm:ss.sTZD, e.g.
     * 2009-07-24T19:20:30.123+02:00).
     *
     * @var DateTimeFormatInterface
     * @Serializer\Type("Deviantintegral\NullDateTime\DateTimeFormatInterface")
     */
    private $expires;

    /**
     * @return DateTimeFormatInterface
     */
    public function getExpires(): DateTimeFormatInterface
    {
        return $this->expires;
    }

    /**
     * @param \DateTime $expires
     *
     * @return self
     */
    public function setExpires(DateTimeFormatInterface $expires): self
    {
        $this->expires = $expires;

        return $this;
    }
}
