<?php

declare(strict_types=1);

namespace Deviantintegral\Har\Validation;

/**
 * Represents a single validation error found during HAR validation.
 */
final class ValidationError
{
    /**
     * @param string      $message  The error message describing the validation failure
     * @param string      $path     The JSON path to the invalid field (e.g., "log.entries[0].request")
     * @param string|null $property The specific property that failed validation
     */
    public function __construct(
        private readonly string $message,
        private readonly string $path = '',
        private readonly ?string $property = null,
    ) {
    }

    /**
     * Get the error message.
     */
    public function getMessage(): string
    {
        return $this->message;
    }

    /**
     * Get the JSON path to the invalid field.
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * Get the specific property that failed validation.
     */
    public function getProperty(): ?string
    {
        return $this->property;
    }

    /**
     * Get a formatted error message including the path.
     */
    public function getFullMessage(): string
    {
        if ('' === $this->path) {
            return $this->message;
        }

        return \sprintf('[%s] %s', $this->path, $this->message);
    }
}
