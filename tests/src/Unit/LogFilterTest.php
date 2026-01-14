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
 * @covers \Deviantintegral\Har\Log::filterEntriesByMethod
 * @covers \Deviantintegral\Har\Log::filterEntriesByStatus
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

    public function testFilterEntriesByMethodWithFixture(): void
    {
        $repository = $this->getHarFileRepository();
        $har = $repository->load('www.softwareishard.com-multiple-entries.har');
        $log = $har->getLog();

        // All entries in fixture are GET
        $getEntries = $log->filterEntriesByMethod('GET');
        $this->assertCount(\count($log->getEntries()), $getEntries);

        // No POST entries
        $postEntries = $log->filterEntriesByMethod('POST');
        $this->assertEmpty($postEntries);
    }

    public function testFilterEntriesByMethodCaseInsensitive(): void
    {
        $log = $this->createLogWithTestEntries();

        $upperResults = $log->filterEntriesByMethod('POST');
        $lowerResults = $log->filterEntriesByMethod('post');
        $mixedResults = $log->filterEntriesByMethod('Post');

        $this->assertEquals($upperResults, $lowerResults);
        $this->assertEquals($upperResults, $mixedResults);
        $this->assertCount(1, $upperResults);
    }

    public function testFilterEntriesByMethodNormalizesEntryMethod(): void
    {
        // Create an entry with lowercase method to test that the filter
        // normalizes the entry's method, not just the filter parameter
        $entry = $this->createEntry('get', 'https://example.com/test', 200, 'text/html');
        $log = (new Log())->setEntries([$entry]);

        $results = $log->filterEntriesByMethod('GET');
        $this->assertCount(1, $results);
    }

    public function testFilterEntriesByMethodReturnsCorrectEntries(): void
    {
        $log = $this->createLogWithTestEntries();

        $getEntries = $log->filterEntriesByMethod('GET');
        $this->assertCount(3, $getEntries);
        foreach ($getEntries as $entry) {
            $this->assertSame('GET', $entry->getRequest()->getMethod());
        }

        $postEntries = $log->filterEntriesByMethod('POST');
        $this->assertCount(1, $postEntries);
        $this->assertSame('POST', $postEntries[0]->getRequest()->getMethod());

        $deleteEntries = $log->filterEntriesByMethod('DELETE');
        $this->assertCount(1, $deleteEntries);
    }

    public function testFilterEntriesByMethodReturnsReindexedArray(): void
    {
        $log = $this->createLogWithTestEntries();

        // POST is at index 1 in the entries array
        // Without array_values, result would have key [1]
        // With array_values, result should have key [0]
        $results = $log->filterEntriesByMethod('POST');
        $this->assertCount(1, $results);
        $this->assertSame([0], array_keys($results));
    }

    public function testFilterEntriesByMethodOnEmptyEntries(): void
    {
        $log = (new Log())->setEntries([]);

        $this->assertEmpty($log->filterEntriesByMethod('GET'));
    }

    public function testFilterEntriesByStatusWithFixture(): void
    {
        $repository = $this->getHarFileRepository();
        $har = $repository->load('www.softwareishard.com-multiple-entries.har');
        $log = $har->getLog();

        // All entries should have 2xx status codes
        $successEntries = $log->filterEntriesByStatus(200, 299);
        $this->assertNotEmpty($successEntries);
        foreach ($successEntries as $entry) {
            $status = $entry->getResponse()->getStatus();
            $this->assertGreaterThanOrEqual(200, $status);
            $this->assertLessThanOrEqual(299, $status);
        }

        // No 5xx errors expected in fixture
        $serverErrors = $log->filterEntriesByStatus(500, 599);
        $this->assertEmpty($serverErrors);
    }

    public function testFilterEntriesByStatusReturnsCorrectEntries(): void
    {
        $log = $this->createLogWithTestEntries();

        // Test entries have: 200, 201, 404, 200, 500
        // Filter 2xx (200-299)
        $successEntries = $log->filterEntriesByStatus(200, 299);
        $this->assertCount(3, $successEntries);
        foreach ($successEntries as $entry) {
            $status = $entry->getResponse()->getStatus();
            $this->assertGreaterThanOrEqual(200, $status);
            $this->assertLessThanOrEqual(299, $status);
        }

        // Filter 4xx (400-499)
        $clientErrors = $log->filterEntriesByStatus(400, 499);
        $this->assertCount(1, $clientErrors);
        $this->assertSame(404, $clientErrors[0]->getResponse()->getStatus());

        // Filter 5xx (500-599)
        $serverErrors = $log->filterEntriesByStatus(500, 599);
        $this->assertCount(1, $serverErrors);
        $this->assertSame(500, $serverErrors[0]->getResponse()->getStatus());
    }

    public function testFilterEntriesByStatusBoundaryConditions(): void
    {
        $log = $this->createLogWithTestEntries();

        // Test inclusive boundary - exactly 200
        $exact200 = $log->filterEntriesByStatus(200, 200);
        $this->assertCount(2, $exact200);
        foreach ($exact200 as $entry) {
            $this->assertSame(200, $entry->getResponse()->getStatus());
        }

        // Test boundary at min - status 201 should match min=201
        $atMin = $log->filterEntriesByStatus(201, 201);
        $this->assertCount(1, $atMin);
        $this->assertSame(201, $atMin[0]->getResponse()->getStatus());

        // Test boundary at max - status 201 should match max=201
        $upTo201 = $log->filterEntriesByStatus(200, 201);
        $this->assertCount(3, $upTo201);

        // Test that 199 doesn't match when min=200
        $noMatch = $log->filterEntriesByStatus(199, 199);
        $this->assertEmpty($noMatch);

        // Test that 501 doesn't match when max=500
        $upTo500 = $log->filterEntriesByStatus(500, 500);
        $this->assertCount(1, $upTo500);
    }

    public function testFilterEntriesByStatusReturnsReindexedArray(): void
    {
        $log = $this->createLogWithTestEntries();

        // Test entries: 200 (idx 0), 201 (idx 1), 404 (idx 2), 200 (idx 3), 500 (idx 4)
        // Filter 4xx should match only index 2
        // Without array_values, result would have key [2]
        // With array_values, result should have key [0]
        $results = $log->filterEntriesByStatus(400, 499);
        $this->assertCount(1, $results);
        $this->assertSame([0], array_keys($results));
    }

    public function testFilterEntriesByStatusOnEmptyEntries(): void
    {
        $log = (new Log())->setEntries([]);

        $this->assertEmpty($log->filterEntriesByStatus(200, 299));
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
