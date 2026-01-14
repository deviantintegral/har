<?php

declare(strict_types=1);

namespace Deviantintegral\Har\Tests\Unit;

use Deviantintegral\Har\Content;
use Deviantintegral\Har\Entry;
use Deviantintegral\Har\Log;
use Deviantintegral\Har\Request;
use Deviantintegral\Har\Response;
use GuzzleHttp\Psr7\Uri;

/**
 * @covers \Deviantintegral\Har\Log::filterEntriesByUrlPattern
 */
class LogFilterTest extends HarTestBase
{
    public function testFilterEntriesByUrlPatternWithFixture(): void
    {
        $repository = $this->getHarFileRepository();
        $har = $repository->load('www.softwareishard.com-multiple-entries.har');
        $log = $har->getLog();

        // Filter for CSS files
        $cssEntries = $log->filterEntriesByUrlPattern('/\.css/');
        $this->assertNotEmpty($cssEntries);
        foreach ($cssEntries as $entry) {
            $this->assertStringContainsString('.css', (string) $entry->getRequest()->getUrl());
        }

        // Filter for JS files
        $jsEntries = $log->filterEntriesByUrlPattern('/\.js/');
        $this->assertNotEmpty($jsEntries);
        foreach ($jsEntries as $entry) {
            $this->assertStringContainsString('.js', (string) $entry->getRequest()->getUrl());
        }

        // Filter for specific path pattern
        $wpContentEntries = $log->filterEntriesByUrlPattern('/\/wp-content\//');
        $this->assertNotEmpty($wpContentEntries);
        foreach ($wpContentEntries as $entry) {
            $this->assertStringContainsString('/wp-content/', (string) $entry->getRequest()->getUrl());
        }
    }

    public function testFilterEntriesByUrlPatternWithRegexGroups(): void
    {
        $log = $this->createLogWithTestEntries();

        // Test complex regex with groups
        $results = $log->filterEntriesByUrlPattern('/\/api\/users\/\d+/');
        $this->assertCount(2, $results);
    }

    public function testFilterEntriesByUrlPatternReturnsEmptyForNoMatches(): void
    {
        $repository = $this->getHarFileRepository();
        $har = $repository->load('www.softwareishard.com-multiple-entries.har');
        $log = $har->getLog();

        $results = $log->filterEntriesByUrlPattern('/nonexistent-pattern-xyz/');
        $this->assertEmpty($results);
    }

    public function testFilterEntriesByUrlPatternReturnsReindexedArray(): void
    {
        $log = $this->createLogWithTestEntries();

        // Filter for entries with numeric user IDs - matches entries at indices 0 and 2
        // Without array_values, result would have keys [0, 2]
        // With array_values, result should have keys [0, 1]
        $results = $log->filterEntriesByUrlPattern('/\/users\/\d+/');
        $this->assertCount(2, $results);
        $this->assertSame([0, 1], array_keys($results));
    }

    public function testFilterEntriesByUrlPatternOnEmptyEntries(): void
    {
        $log = (new Log())->setEntries([]);

        $this->assertEmpty($log->filterEntriesByUrlPattern('/test/'));
    }

    /**
     * Creates a Log with test entries for programmatic testing.
     */
    private function createLogWithTestEntries(): Log
    {
        $entries = [
            // Entry 1: GET /api/users/123, 200, application/json, api.example.com
            $this->createEntry('GET', 'https://api.example.com/api/users/123', 200, 'application/json'),
            // Entry 2: POST /api/users, 201, application/json, api.example.com
            $this->createEntry('POST', 'https://api.example.com/api/users', 201, 'application/json'),
            // Entry 3: GET /api/users/456, 404, text/html, api.example.com
            $this->createEntry('GET', 'https://api.example.com/api/users/456', 404, 'text/html'),
            // Entry 4: GET /images/logo.png, 200, image/png, cdn.example.com
            $this->createEntry('GET', 'https://cdn.example.com/images/logo.png', 200, 'image/png'),
            // Entry 5: DELETE /api/resource/789, 500, image/jpeg, cdn.example.com
            $this->createEntry('DELETE', 'https://cdn.example.com/api/resource/789', 500, 'image/jpeg'),
        ];

        return (new Log())->setEntries($entries);
    }

    private function createEntry(string $method, string $url, int $status, string $contentType): Entry
    {
        $request = (new Request())
            ->setMethod($method)
            ->setUrl(new Uri($url));

        $content = (new Content())
            ->setMimeType($contentType)
            ->setSize(0);

        $response = (new Response())
            ->setStatus($status)
            ->setStatusText($this->getStatusText($status))
            ->setContent($content);

        return (new Entry())
            ->setRequest($request)
            ->setResponse($response);
    }

    private function getStatusText(int $status): string
    {
        return match ($status) {
            200 => 'OK',
            201 => 'Created',
            404 => 'Not Found',
            500 => 'Internal Server Error',
            default => 'Unknown',
        };
    }
}
