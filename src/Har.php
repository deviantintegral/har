<?php

declare(strict_types=1);

namespace Deviantintegral\Har;

use JMS\Serializer\Annotation as Serializer;

final class Har
{
    /**
     * @Serializer\Type("Deviantintegral\Har\Log")
     */
    private Log $log;

    public function getLog(): Log
    {
        return $this->log;
    }

    public function setLog(Log $log): self
    {
        $this->log = $log;

        return $this;
    }

    /**
     * Return a generator that returns cloned HARs with one per HAR entry.
     *
     * @return Har[]
     */
    public function splitLogEntries(): \Generator
    {
        foreach ($this->getLog()->getEntries() as $index => $entry) {
            $cloned = clone $this;
            $cloned->getLog()->setEntries([$entry]);
            yield $index => $cloned;
        }
    }
}
