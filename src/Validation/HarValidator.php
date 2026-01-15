<?php

declare(strict_types=1);

namespace Deviantintegral\Har\Validation;

use Deviantintegral\Har\Cache;
use Deviantintegral\Har\CacheState;
use Deviantintegral\Har\Content;
use Deviantintegral\Har\Cookie;
use Deviantintegral\Har\Creator;
use Deviantintegral\Har\Entry;
use Deviantintegral\Har\Har;
use Deviantintegral\Har\Header;
use Deviantintegral\Har\Log;
use Deviantintegral\Har\Page;
use Deviantintegral\Har\Params;
use Deviantintegral\Har\PostData;
use Deviantintegral\Har\Request;
use Deviantintegral\Har\Response;
use Deviantintegral\Har\Serializer;
use Deviantintegral\Har\Timings;

/**
 * Validates HAR files against the HAR 1.2 specification.
 *
 * @see http://www.softwareishard.com/blog/har-12-spec/
 */
final class HarValidator
{
    private Serializer $serializer;

    public function __construct(?Serializer $serializer = null)
    {
        $this->serializer = $serializer ?? new Serializer();
    }

    /**
     * Validate a HAR object against the 1.2 specification.
     */
    public function validate(Har $har): ValidationResult
    {
        // Validate the log property exists
        try {
            $log = $har->getLog();

            return new ValidationResult($this->validateLog($log, 'log'));
        } catch (\Error) {
            return new ValidationResult([
                new ValidationError(
                    'HAR must contain a "log" property',
                    'log',
                    'log'
                ),
            ]);
        }
    }

    /**
     * Validate a JSON string as a HAR file.
     *
     * This method first validates the JSON structure, then deserializes
     * and validates the HAR object.
     */
    public function validateJson(string $json): ValidationResult
    {
        // Remove BOM if present
        $json = $this->serializer->removeBOM($json);

        // First, check if it's valid JSON
        try {
            $decoded = json_decode($json, true, 512, \JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            return ValidationResult::invalid([
                new ValidationError(
                    \sprintf('Invalid JSON: %s', $e->getMessage()),
                    '',
                    null
                ),
            ]);
        }

        // Check for required root structure
        if (!\is_array($decoded)) {
            return ValidationResult::invalid([
                new ValidationError(
                    'HAR must be a JSON object',
                    '',
                    null
                ),
            ]);
        }

        if (!isset($decoded['log'])) {
            return ValidationResult::invalid([
                new ValidationError(
                    'HAR must contain a "log" property',
                    'log',
                    'log'
                ),
            ]);
        }

        // Validate the raw JSON structure before deserializing
        $structuralErrors = $this->validateJsonLog($decoded['log'], 'log');

        // If there are structural errors, return early
        if ([] !== $structuralErrors) {
            return new ValidationResult($structuralErrors);
        }

        // Try to deserialize and validate the HAR object
        try {
            $har = $this->serializer->deserializeHar($json);

            return $this->validate($har);
        } catch (\Exception $e) {
            return ValidationResult::invalid([
                new ValidationError(
                    \sprintf('Failed to parse HAR: %s', $e->getMessage()),
                    '',
                    null
                ),
            ]);
        }
    }

    /**
     * Validate the log object.
     *
     * @return ValidationError[]
     */
    private function validateLog(Log $log, string $path): array
    {
        $errors = [];

        // Version is required
        try {
            $_ = $log->getVersion();
        } catch (\Error) {
            $errors[] = new ValidationError(
                'Log must have a "version" property',
                $path,
                'version'
            );
        }

        // Creator is required
        try {
            $creator = $log->getCreator();
            $errors = array_merge($errors, $this->validateCreator($creator, $path.'.creator'));
        } catch (\Error) {
            $errors[] = new ValidationError(
                'Log must have a "creator" property',
                $path,
                'creator'
            );
        }

        // Browser is optional, but if present must be valid
        try {
            $browser = $log->getBrowser();
            $errors = array_merge($errors, $this->validateCreator($browser, $path.'.browser'));
        } catch (\Error) {
            // Browser is optional, so no error if not set
        }

        // Entries is required
        try {
            $entries = $log->getEntries();
            foreach ($entries as $index => $entry) {
                $errors = array_merge($errors, $this->validateEntry($entry, $path.'.entries['.$index.']'));
            }
        } catch (\Error) {
            $errors[] = new ValidationError(
                'Log must have an "entries" property',
                $path,
                'entries'
            );
        }

        // Pages is optional, but if present must be valid
        try {
            $pages = $log->getPages();
            $pageIds = [];
            foreach ($pages as $index => $page) {
                $errors = array_merge($errors, $this->validatePage($page, $path.'.pages['.$index.']'));
                // Collect page IDs for entry pageref validation
                try {
                    $pageIds[] = $page->getId();
                } catch (\Error) {
                    // ID validation is handled in validatePage
                }
            }

            // Validate entry pagerefs reference valid page IDs
            if ([] !== $pageIds) {
                try {
                    $entries = $log->getEntries();
                    foreach ($entries as $index => $entry) {
                        $errors = array_merge(
                            $errors,
                            $this->validateEntryPageref($entry, $pageIds, $path.'.entries['.$index.']')
                        );
                    }
                } catch (\Error) {
                    // Entries validation is handled above
                }
            }
        } catch (\Error) {
            // Pages is optional, so no error if not set
        }

        return $errors;
    }

    /**
     * Validate a creator or browser object.
     *
     * @return ValidationError[]
     */
    private function validateCreator(Creator $creator, string $path): array
    {
        $errors = [];

        try {
            $_ = $creator->getName();
        } catch (\Error) {
            $errors[] = new ValidationError(
                'Creator must have a "name" property',
                $path,
                'name'
            );
        }

        try {
            $_ = $creator->getVersion();
        } catch (\Error) {
            $errors[] = new ValidationError(
                'Creator must have a "version" property',
                $path,
                'version'
            );
        }

        return $errors;
    }

    /**
     * Validate a page object.
     *
     * @return ValidationError[]
     */
    private function validatePage(Page $page, string $path): array
    {
        $errors = [];

        try {
            $_ = $page->getStartedDateTime();
        } catch (\Error) {
            $errors[] = new ValidationError(
                'Page must have a "startedDateTime" property',
                $path,
                'startedDateTime'
            );
        }

        try {
            $_ = $page->getId();
        } catch (\Error) {
            $errors[] = new ValidationError(
                'Page must have an "id" property',
                $path,
                'id'
            );
        }

        try {
            $_ = $page->getTitle();
        } catch (\Error) {
            $errors[] = new ValidationError(
                'Page must have a "title" property',
                $path,
                'title'
            );
        }

        try {
            // Just verify pageTimings exists - all its fields are optional
            $_ = $page->getPageTimings();
        } catch (\Error) {
            $errors[] = new ValidationError(
                'Page must have a "pageTimings" property',
                $path,
                'pageTimings'
            );
        }

        return $errors;
    }

    /**
     * Validate an entry object.
     *
     * @return ValidationError[]
     */
    private function validateEntry(Entry $entry, string $path): array
    {
        $errors = [];

        try {
            $_ = $entry->getStartedDateTime();
        } catch (\Error) {
            $errors[] = new ValidationError(
                'Entry must have a "startedDateTime" property',
                $path,
                'startedDateTime'
            );
        }

        try {
            $time = $entry->getTime();
            if ($time < 0) {
                $errors[] = new ValidationError(
                    'Entry "time" must be non-negative',
                    $path,
                    'time'
                );
            }
        } catch (\Error) {
            $errors[] = new ValidationError(
                'Entry must have a "time" property',
                $path,
                'time'
            );
        }

        try {
            $request = $entry->getRequest();
            $errors = array_merge($errors, $this->validateRequest($request, $path.'.request'));
        } catch (\Error) {
            $errors[] = new ValidationError(
                'Entry must have a "request" property',
                $path,
                'request'
            );
        }

        try {
            $response = $entry->getResponse();
            $errors = array_merge($errors, $this->validateResponse($response, $path.'.response'));
        } catch (\Error) {
            $errors[] = new ValidationError(
                'Entry must have a "response" property',
                $path,
                'response'
            );
        }

        try {
            $cache = $entry->getCache();
            $errors = array_merge($errors, $this->validateCache($cache, $path.'.cache'));
        } catch (\Error) {
            $errors[] = new ValidationError(
                'Entry must have a "cache" property',
                $path,
                'cache'
            );
        }

        try {
            $timings = $entry->getTimings();
            $errors = array_merge($errors, $this->validateTimings($timings, $path.'.timings'));
        } catch (\Error) {
            $errors[] = new ValidationError(
                'Entry must have a "timings" property',
                $path,
                'timings'
            );
        }

        return $errors;
    }

    /**
     * Validate entry pageref references a valid page ID.
     *
     * @param string[] $pageIds
     *
     * @return ValidationError[]
     */
    private function validateEntryPageref(Entry $entry, array $pageIds, string $path): array
    {
        $errors = [];

        try {
            $pageref = $entry->getPageref();
            if (!\in_array($pageref, $pageIds, true)) {
                $errors[] = new ValidationError(
                    \sprintf('Entry "pageref" must reference a valid page ID, got "%s"', $pageref),
                    $path,
                    'pageref'
                );
            }
        } catch (\Error) {
            // pageref is optional
        }

        return $errors;
    }

    /**
     * Validate a request object.
     *
     * @return ValidationError[]
     */
    private function validateRequest(Request $request, string $path): array
    {
        $errors = [];

        try {
            $_ = $request->getMethod();
        } catch (\Error) {
            $errors[] = new ValidationError(
                'Request must have a "method" property',
                $path,
                'method'
            );
        }

        try {
            $_ = $request->getUrl();
        } catch (\Error) {
            $errors[] = new ValidationError(
                'Request must have a "url" property',
                $path,
                'url'
            );
        }

        try {
            $_ = $request->getHttpVersion();
        } catch (\Error) {
            $errors[] = new ValidationError(
                'Request must have an "httpVersion" property',
                $path,
                'httpVersion'
            );
        }

        // Cookies array is required (can be empty)
        try {
            $cookies = $request->getCookies();
            foreach ($cookies as $index => $cookie) {
                $errors = array_merge($errors, $this->validateCookie($cookie, $path.'.cookies['.$index.']'));
            }
        } catch (\Error) {
            $errors[] = new ValidationError(
                'Request must have a "cookies" property',
                $path,
                'cookies'
            );
        }

        // Headers array is required (can be empty)
        try {
            $headers = $request->getHeaders();
            foreach ($headers as $index => $header) {
                $errors = array_merge($errors, $this->validateHeader($header, $path.'.headers['.$index.']'));
            }
        } catch (\Error) {
            $errors[] = new ValidationError(
                'Request must have a "headers" property',
                $path,
                'headers'
            );
        }

        // QueryString array is required (can be empty) - though the codebase returns [] by default
        $queryString = $request->getQueryString();
        foreach ($queryString as $index => $param) {
            $errors = array_merge($errors, $this->validateParams($param, $path.'.queryString['.$index.']'));
        }

        // PostData is optional
        if ($request->hasPostData()) {
            $errors = array_merge($errors, $this->validatePostData($request->getPostData(), $path.'.postData'));
        }

        // headersSize is required (can be -1 if not available)
        try {
            $headersSize = $request->getHeadersSize();
            if ($headersSize < -1) {
                $errors[] = new ValidationError(
                    'Request "headersSize" must be >= -1',
                    $path,
                    'headersSize'
                );
            }
        } catch (\Error) {
            $errors[] = new ValidationError(
                'Request must have a "headersSize" property',
                $path,
                'headersSize'
            );
        }

        // bodySize is required (can be -1 if not available)
        try {
            $bodySize = $request->getBodySize();
            if ($bodySize < -1) {
                $errors[] = new ValidationError(
                    'Request "bodySize" must be >= -1',
                    $path,
                    'bodySize'
                );
            }
        } catch (\Error) {
            $errors[] = new ValidationError(
                'Request must have a "bodySize" property',
                $path,
                'bodySize'
            );
        }

        return $errors;
    }

    /**
     * Validate a response object.
     *
     * @return ValidationError[]
     */
    private function validateResponse(Response $response, string $path): array
    {
        $errors = [];

        try {
            $_ = $response->getStatus();
        } catch (\Error) {
            $errors[] = new ValidationError(
                'Response must have a "status" property',
                $path,
                'status'
            );
        }

        try {
            $_ = $response->getStatusText();
        } catch (\Error) {
            $errors[] = new ValidationError(
                'Response must have a "statusText" property',
                $path,
                'statusText'
            );
        }

        try {
            $_ = $response->getHttpVersion();
        } catch (\Error) {
            $errors[] = new ValidationError(
                'Response must have an "httpVersion" property',
                $path,
                'httpVersion'
            );
        }

        // Cookies array is required (can be empty)
        try {
            $cookies = $response->getCookies();
            foreach ($cookies as $index => $cookie) {
                $errors = array_merge($errors, $this->validateCookie($cookie, $path.'.cookies['.$index.']'));
            }
        } catch (\Error) {
            $errors[] = new ValidationError(
                'Response must have a "cookies" property',
                $path,
                'cookies'
            );
        }

        // Headers array is required (can be empty)
        try {
            $headers = $response->getHeaders();
            foreach ($headers as $index => $header) {
                $errors = array_merge($errors, $this->validateHeader($header, $path.'.headers['.$index.']'));
            }
        } catch (\Error) {
            $errors[] = new ValidationError(
                'Response must have a "headers" property',
                $path,
                'headers'
            );
        }

        try {
            $content = $response->getContent();
            $errors = array_merge($errors, $this->validateContent($content, $path.'.content'));
        } catch (\Error) {
            $errors[] = new ValidationError(
                'Response must have a "content" property',
                $path,
                'content'
            );
        }

        try {
            $_ = $response->getRedirectURL();
        } catch (\Error) {
            $errors[] = new ValidationError(
                'Response must have a "redirectURL" property',
                $path,
                'redirectURL'
            );
        }

        // headersSize is required (can be -1 if not available)
        try {
            $headersSize = $response->getHeadersSize();
            if ($headersSize < -1) {
                $errors[] = new ValidationError(
                    'Response "headersSize" must be >= -1',
                    $path,
                    'headersSize'
                );
            }
        } catch (\Error) {
            $errors[] = new ValidationError(
                'Response must have a "headersSize" property',
                $path,
                'headersSize'
            );
        }

        // bodySize is required (can be -1 if not available)
        try {
            $bodySize = $response->getBodySize();
            if ($bodySize < -1) {
                $errors[] = new ValidationError(
                    'Response "bodySize" must be >= -1',
                    $path,
                    'bodySize'
                );
            }
        } catch (\Error) {
            $errors[] = new ValidationError(
                'Response must have a "bodySize" property',
                $path,
                'bodySize'
            );
        }

        return $errors;
    }

    /**
     * Validate a cookie object.
     *
     * @return ValidationError[]
     */
    private function validateCookie(Cookie $cookie, string $path): array
    {
        $errors = [];

        try {
            $_ = $cookie->getName();
        } catch (\Error) {
            $errors[] = new ValidationError(
                'Cookie must have a "name" property',
                $path,
                'name'
            );
        }

        try {
            $_ = $cookie->getValue();
        } catch (\Error) {
            $errors[] = new ValidationError(
                'Cookie must have a "value" property',
                $path,
                'value'
            );
        }

        return $errors;
    }

    /**
     * Validate a header object.
     *
     * @return ValidationError[]
     */
    private function validateHeader(Header $header, string $path): array
    {
        $errors = [];

        try {
            $_ = $header->getName();
        } catch (\Error) {
            $errors[] = new ValidationError(
                'Header must have a "name" property',
                $path,
                'name'
            );
        }

        try {
            $_ = $header->getValue();
        } catch (\Error) {
            $errors[] = new ValidationError(
                'Header must have a "value" property',
                $path,
                'value'
            );
        }

        return $errors;
    }

    /**
     * Validate a params object (query string or post params).
     *
     * @return ValidationError[]
     */
    private function validateParams(Params $params, string $path): array
    {
        $errors = [];

        try {
            $_ = $params->getName();
        } catch (\Error) {
            $errors[] = new ValidationError(
                'Params must have a "name" property',
                $path,
                'name'
            );
        }

        // value is optional for params (file uploads may not have value)

        return $errors;
    }

    /**
     * Validate post data object.
     *
     * @return ValidationError[]
     */
    private function validatePostData(PostData $postData, string $path): array
    {
        $errors = [];

        try {
            $_ = $postData->getMimeType();
        } catch (\Error) {
            $errors[] = new ValidationError(
                'PostData must have a "mimeType" property',
                $path,
                'mimeType'
            );
        }

        // params and text are mutually exclusive, at least one should be present
        // but this is handled by the entity class

        return $errors;
    }

    /**
     * Validate content object.
     *
     * @return ValidationError[]
     */
    private function validateContent(Content $content, string $path): array
    {
        $errors = [];

        try {
            $_ = $content->getSize();
        } catch (\Error) {
            $errors[] = new ValidationError(
                'Content must have a "size" property',
                $path,
                'size'
            );
        }

        try {
            $_ = $content->getMimeType();
        } catch (\Error) {
            $errors[] = new ValidationError(
                'Content must have a "mimeType" property',
                $path,
                'mimeType'
            );
        }

        return $errors;
    }

    /**
     * Validate cache object.
     *
     * @return ValidationError[]
     */
    private function validateCache(Cache $cache, string $path): array
    {
        $errors = [];

        // beforeRequest and afterRequest are both optional
        if ($cache->hasBeforeRequest()) {
            $errors = array_merge(
                $errors,
                $this->validateCacheState($cache->getBeforeRequest(), $path.'.beforeRequest')
            );
        }

        if ($cache->hasAfterRequest()) {
            $errors = array_merge(
                $errors,
                $this->validateCacheState($cache->getAfterRequest(), $path.'.afterRequest')
            );
        }

        return $errors;
    }

    /**
     * Validate cache state object.
     *
     * @return ValidationError[]
     */
    private function validateCacheState(CacheState $cacheState, string $path): array
    {
        $errors = [];

        try {
            $_ = $cacheState->getLastAccess();
        } catch (\Error) {
            $errors[] = new ValidationError(
                'CacheState must have a "lastAccess" property',
                $path,
                'lastAccess'
            );
        }

        try {
            $_ = $cacheState->getETag();
        } catch (\Error) {
            $errors[] = new ValidationError(
                'CacheState must have an "eTag" property',
                $path,
                'eTag'
            );
        }

        try {
            $_ = $cacheState->getHitCount();
        } catch (\Error) {
            $errors[] = new ValidationError(
                'CacheState must have a "hitCount" property',
                $path,
                'hitCount'
            );
        }

        return $errors;
    }

    /**
     * Validate timings object.
     *
     * @return ValidationError[]
     */
    private function validateTimings(Timings $timings, string $path): array
    {
        $errors = [];

        // send, wait, receive are required and must be >= 0
        try {
            $send = $timings->getSend();
            if ($send < 0) {
                $errors[] = new ValidationError(
                    'Timings "send" must be non-negative',
                    $path,
                    'send'
                );
            }
        } catch (\Error) {
            $errors[] = new ValidationError(
                'Timings must have a "send" property',
                $path,
                'send'
            );
        }

        try {
            $wait = $timings->getWait();
            if ($wait < 0) {
                $errors[] = new ValidationError(
                    'Timings "wait" must be non-negative',
                    $path,
                    'wait'
                );
            }
        } catch (\Error) {
            $errors[] = new ValidationError(
                'Timings must have a "wait" property',
                $path,
                'wait'
            );
        }

        try {
            $receive = $timings->getReceive();
            if ($receive < 0) {
                $errors[] = new ValidationError(
                    'Timings "receive" must be non-negative',
                    $path,
                    'receive'
                );
            }
        } catch (\Error) {
            $errors[] = new ValidationError(
                'Timings must have a "receive" property',
                $path,
                'receive'
            );
        }

        // blocked, dns, connect, ssl are optional (default to -1)

        return $errors;
    }

    /**
     * Validate log from raw JSON structure.
     *
     * @param array<string, mixed> $log
     *
     * @return ValidationError[]
     */
    private function validateJsonLog(array $log, string $path): array
    {
        $errors = [];

        if (!isset($log['version'])) {
            $errors[] = new ValidationError(
                'Log must have a "version" property',
                $path,
                'version'
            );
        }

        if (!isset($log['creator'])) {
            $errors[] = new ValidationError(
                'Log must have a "creator" property',
                $path,
                'creator'
            );
        } elseif (\is_array($log['creator'])) {
            $errors = array_merge($errors, $this->validateJsonCreator($log['creator'], $path.'.creator'));
        }

        if (!isset($log['entries'])) {
            $errors[] = new ValidationError(
                'Log must have an "entries" property',
                $path,
                'entries'
            );
        } elseif (!\is_array($log['entries'])) {
            $errors[] = new ValidationError(
                'Log "entries" must be an array',
                $path,
                'entries'
            );
        }

        return $errors;
    }

    /**
     * Validate creator from raw JSON structure.
     *
     * @param array<string, mixed> $creator
     *
     * @return ValidationError[]
     */
    private function validateJsonCreator(array $creator, string $path): array
    {
        $errors = [];

        if (!isset($creator['name'])) {
            $errors[] = new ValidationError(
                'Creator must have a "name" property',
                $path,
                'name'
            );
        }

        if (!isset($creator['version'])) {
            $errors[] = new ValidationError(
                'Creator must have a "version" property',
                $path,
                'version'
            );
        }

        return $errors;
    }
}
