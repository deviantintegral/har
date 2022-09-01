<?php

declare(strict_types=1);

namespace Deviantintegral\Har\SharedFields;

trait StartedDateTimeTrait
{
    /**
     * Date and time stamp of the request start.
     *
     * @var \DateTime
     *
     * @Serializer\Type("DateTime")
     */
    private $startedDateTime;

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
