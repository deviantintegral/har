<?php

declare(strict_types=1);

namespace Deviantintegral\Har;

/**
 * Sanitizes HAR files by redacting sensitive data.
 *
 * Use case: Before committing HAR fixtures to a repository, sensitive
 * authentication data should be removed.
 *
 * @see http://www.softwareishard.com/blog/har-12-spec/
 */
final class HarSanitizer
{
    private const DEFAULT_REDACTED_VALUE = '[REDACTED]';

    /**
     * @var string[]
     */
    private array $headersToRedact = [];

    private string $redactedValue = self::DEFAULT_REDACTED_VALUE;

    private bool $caseSensitive = false;

    /**
     * Set headers that should be redacted.
     *
     * @param string[] $headerNames header names to redact (case-insensitive by default)
     */
    public function redactHeaders(array $headerNames): self
    {
        $this->headersToRedact = $headerNames;

        return $this;
    }

    /**
     * Set the value to use for redacted fields.
     *
     * Defaults to "[REDACTED]".
     */
    public function setRedactedValue(string $value): self
    {
        $this->redactedValue = $value;

        return $this;
    }

    /**
     * Set whether name matching should be case-sensitive.
     *
     * Defaults to false (case-insensitive matching).
     */
    public function setCaseSensitive(bool $caseSensitive): self
    {
        $this->caseSensitive = $caseSensitive;

        return $this;
    }

    /**
     * Sanitize a HAR by redacting configured sensitive fields.
     *
     * Returns a new Har instance with redacted values. The original is not modified.
     */
    public function sanitize(Har $har): Har
    {
        // Clone to avoid modifying the original
        $sanitized = clone $har;

        foreach ($sanitized->getLog()->getEntries() as $entry) {
            $this->sanitizeEntry($entry);
        }

        return $sanitized;
    }

    /**
     * Sanitize a single entry.
     */
    private function sanitizeEntry(Entry $entry): void
    {
        $this->sanitizeRequest($entry->getRequest());
        $this->sanitizeResponse($entry->getResponse());
    }

    /**
     * Sanitize request headers.
     */
    private function sanitizeRequest(Request $request): void
    {
        if (!empty($this->headersToRedact)) {
            $this->sanitizeHeaders($request);
        }
    }

    /**
     * Sanitize response headers.
     */
    private function sanitizeResponse(Response $response): void
    {
        if (!empty($this->headersToRedact)) {
            $this->sanitizeHeaders($response);
        }
    }

    /**
     * Sanitize headers on a message (request or response).
     */
    private function sanitizeHeaders(MessageInterface $message): void
    {
        $headers = $message->getHeaders();

        foreach ($headers as $header) {
            if ($this->shouldRedact($header->getName(), $this->headersToRedact)) {
                $header->setValue($this->redactedValue);
            }
        }

        // Recalculate headers size
        $message->setHeaders($headers);
    }

    /**
     * Check if a name should be redacted based on the configured names.
     *
     * @param string[] $namesToRedact
     */
    private function shouldRedact(string $name, array $namesToRedact): bool
    {
        foreach ($namesToRedact as $toRedact) {
            if ($this->caseSensitive) {
                if ($name === $toRedact) {
                    return true;
                }
            } else {
                if (0 === strcasecmp($name, $toRedact)) {
                    return true;
                }
            }
        }

        return false;
    }
}
