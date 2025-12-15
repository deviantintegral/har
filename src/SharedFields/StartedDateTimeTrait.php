<?php

declare(strict_types=1);

namespace Deviantintegral\Har\SharedFields;

use JMS\Serializer\Annotation as Serializer;

trait StartedDateTimeTrait
{
    /**
     * Date and time stamp of the request start.
     */
    #[Serializer\Type('DateTime')]
    private \DateTime $startedDateTime;

    public function setStartedDateTime(\DateTime $startedDateTime): self
    {
        $this->startedDateTime = $startedDateTime;

        return $this;
    }

    public function getStartedDateTime(): \DateTime
    {
        return $this->startedDateTime;
    }
}
