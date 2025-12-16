<?php

declare(strict_types=1);

namespace Deviantintegral\Har;

use JMS\Serializer\Annotation as Serializer;
use JMS\Serializer\Exception\RuntimeException;

final class Har
{
    #[Serializer\Type("Deviantintegral\Har\Log")]
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
     * Validates that the log property is initialized after deserialization.
     *
     * This method is called automatically by JMS Serializer after deserialization.
     *
     * @throws RuntimeException if the log property is not initialized
     *
     * @phpstan-ignore method.unused
     */
    #[Serializer\PostDeserialize]
    private function validateLogProperty(): void
    {
        if (!isset($this->log)) {
            throw new RuntimeException('HAR file must contain a "log" key');
        }
    }

    /**
     * Deep clone the Log object when cloning Har.
     */
    public function __clone(): void
    {
        $this->log = clone $this->log;
    }

    /**
     * Return a generator that returns cloned HARs with one per HAR entry.
     *
     * @return \Generator<int, Har>
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
