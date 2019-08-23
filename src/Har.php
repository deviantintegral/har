<?php

declare(strict_types=1);

namespace Deviantintegral\Har;

use JMS\Serializer\Annotation as Serializer;

final class Har
{
    /**
     * @var \Deviantintegral\Har\Log
     * @Serializer\Type("Deviantintegral\Har\Log")
     */
    private $log;

    /**
     * @return \Deviantintegral\Har\Log
     */
    public function getLog(): Log
    {
        return $this->log;
    }

    /**
     * @param \Deviantintegral\Har\Log $log
     *
     * @return Har
     */
    public function setLog(Log $log): self
    {
        $this->log = $log;

        return $this;
    }
}
