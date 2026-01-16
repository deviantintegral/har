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

    /**
     * @var string[]
     */
    private array $queryParamsToRedact = [];

    /**
     * @var string[]
     */
    private array $bodyFieldsToRedact = [];

    /**
     * @var string[]
     */
    private array $cookiesToRedact = [];

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
     * Set query parameters that should be redacted.
     *
     * @param string[] $paramNames parameter names to redact
     */
    public function redactQueryParams(array $paramNames): self
    {
        $this->queryParamsToRedact = $paramNames;

        return $this;
    }

    /**
     * Set body fields that should be redacted.
     *
     * Supports both form-encoded POST parameters and JSON body fields.
     * JSON fields are redacted recursively at any nesting level.
     *
     * @param string[] $fieldNames field names to redact
     */
    public function redactBodyFields(array $fieldNames): self
    {
        $this->bodyFieldsToRedact = $fieldNames;

        return $this;
    }

    /**
     * Set cookies that should be redacted.
     *
     * Applies to both request and response cookies.
     *
     * @param string[] $cookieNames cookie names to redact
     */
    public function redactCookies(array $cookieNames): self
    {
        $this->cookiesToRedact = $cookieNames;

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
     * Sanitize request headers, query params, body fields, and cookies.
     */
    private function sanitizeRequest(Request $request): void
    {
        if (!empty($this->headersToRedact)) {
            $this->sanitizeHeaders($request);
        }

        if (!empty($this->queryParamsToRedact)) {
            $this->sanitizeQueryParams($request);
        }

        if (!empty($this->bodyFieldsToRedact) && $request->hasPostData()) {
            $this->sanitizePostData($request->getPostData());
        }

        if (!empty($this->cookiesToRedact)) {
            $this->sanitizeCookies($request);
        }
    }

    /**
     * Sanitize response headers, body fields, and cookies.
     */
    private function sanitizeResponse(Response $response): void
    {
        if (!empty($this->headersToRedact)) {
            $this->sanitizeHeaders($response);
        }

        if (!empty($this->bodyFieldsToRedact)) {
            $this->sanitizeContent($response->getContent());
        }

        if (!empty($this->cookiesToRedact)) {
            $this->sanitizeCookies($response);
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
     * Sanitize query parameters on a request.
     */
    private function sanitizeQueryParams(Request $request): void
    {
        $params = $request->getQueryString();

        foreach ($params as $param) {
            if ($this->shouldRedact($param->getName(), $this->queryParamsToRedact)) {
                $param->setValue($this->redactedValue);
            }
        }

        $request->setQueryString($params);
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

    /**
     * Sanitize POST data (form params and JSON body).
     */
    private function sanitizePostData(PostData $postData): void
    {
        // Sanitize form-encoded parameters
        if ($postData->hasParams()) {
            foreach ($postData->getParams() as $param) {
                if ($this->shouldRedact($param->getName(), $this->bodyFieldsToRedact)) {
                    $param->setValue($this->redactedValue);
                }
            }
        }

        // Sanitize JSON body
        if ($postData->hasText() && $this->isJsonMimeType($postData->getMimeType())) {
            $sanitizedText = $this->sanitizeJsonText($postData->getText());
            if (null !== $sanitizedText) {
                $postData->setText($sanitizedText);
            }
        }
    }

    /**
     * Sanitize response content (JSON body).
     */
    private function sanitizeContent(Content $content): void
    {
        if ($content->hasText() && $this->isJsonMimeType($content->getMimeType())) {
            $sanitizedText = $this->sanitizeJsonText($content->getText());
            if (null !== $sanitizedText) {
                $content->setText($sanitizedText);
            }
        }
    }

    /**
     * Check if a MIME type indicates JSON content.
     */
    private function isJsonMimeType(string $mimeType): bool
    {
        // Match application/json, text/json, and variants like application/vnd.api+json
        return str_contains($mimeType, 'json');
    }

    /**
     * Sanitize JSON text by redacting configured fields.
     *
     * @return string|null the sanitized JSON, or null if parsing failed
     */
    private function sanitizeJsonText(?string $text): ?string
    {
        if (null === $text || '' === $text) {
            return null;
        }

        $data = json_decode($text, true);
        if (\JSON_ERROR_NONE !== json_last_error()) {
            return null;
        }

        $sanitized = $this->redactArrayFields($data);

        return json_encode($sanitized, \JSON_UNESCAPED_SLASHES | \JSON_UNESCAPED_UNICODE);
    }

    /**
     * Recursively redact fields in an array.
     *
     * @param mixed $data the data to sanitize
     *
     * @return mixed the sanitized data
     */
    private function redactArrayFields(mixed $data): mixed
    {
        if (!\is_array($data)) {
            return $data;
        }

        foreach ($data as $key => $value) {
            if (\is_string($key) && $this->shouldRedact($key, $this->bodyFieldsToRedact)) {
                $data[$key] = $this->redactedValue;
            } elseif (\is_array($value)) {
                $data[$key] = $this->redactArrayFields($value);
            }
        }

        return $data;
    }

    /**
     * Sanitize cookies on a request or response.
     */
    private function sanitizeCookies(Request|Response $message): void
    {
        foreach ($message->getCookies() as $cookie) {
            if ($this->shouldRedact($cookie->getName(), $this->cookiesToRedact)) {
                $cookie->setValue($this->redactedValue);
            }
        }
    }
}
