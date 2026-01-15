<?php

declare(strict_types=1);

namespace Deviantintegral\Har\Tests\Unit\Validation;

use Deviantintegral\Har\Browser;
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
use Deviantintegral\Har\PageTimings;
use Deviantintegral\Har\Params;
use Deviantintegral\Har\PostData;
use Deviantintegral\Har\Repository\HarFileRepository;
use Deviantintegral\Har\Request;
use Deviantintegral\Har\Response;
use Deviantintegral\Har\Serializer;
use Deviantintegral\Har\Tests\Unit\HarTestBase;
use Deviantintegral\Har\Timings;
use Deviantintegral\Har\Validation\HarValidator;
use Deviantintegral\Har\Validation\ValidationError;
use GuzzleHttp\Psr7\Uri;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(HarValidator::class)]
class HarValidatorTest extends HarTestBase
{
    private HarValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->validator = new HarValidator();
    }

    public function testConstructorWithCustomSerializerStoresIt(): void
    {
        // Verify the custom serializer is actually stored, not replaced with a new one
        $serializer = new Serializer();
        $validator = new HarValidator($serializer);

        $reflection = new \ReflectionProperty(HarValidator::class, 'serializer');
        $storedSerializer = $reflection->getValue($validator);

        $this->assertSame($serializer, $storedSerializer);
    }

    public function testConstructorWithCustomSerializerUsesIt(): void
    {
        // Also verify it works correctly
        $serializer = new Serializer();
        $validator = new HarValidator($serializer);

        $json = '{"log": {"version": "1.2", "creator": {"name": "test", "version": "1.0"}, "entries": []}}';
        $result = $validator->validateJson($json);

        $this->assertTrue($result->isValid());
    }

    public function testConstructorWithNullSerializerCreatesDefault(): void
    {
        $validator = new HarValidator(null);

        $json = '{"log": {"version": "1.2", "creator": {"name": "test", "version": "1.0"}, "entries": []}}';
        $result = $validator->validateJson($json);

        $this->assertTrue($result->isValid());
    }

    public function testValidateValidMinimalHar(): void
    {
        $har = $this->createMinimalValidHar();

        $result = $this->validator->validate($har);

        $this->assertTrue($result->isValid());
        $this->assertSame(0, $result->getErrorCount());
    }

    #[DataProvider('validFixtureDataProvider')]
    public function testValidateValidFixtures(string $id): void
    {
        $repository = $this->getHarFileRepository();
        $har = $repository->load($id);

        $result = $this->validator->validate($har);

        $this->assertTrue($result->isValid(), \sprintf(
            "Expected HAR fixture '%s' to be valid, but got errors: %s",
            $id,
            implode(', ', array_map(fn (ValidationError $e) => $e->getFullMessage(), $result->getErrors()))
        ));
    }

    /**
     * @return \Generator<array{0: string}>
     */
    public static function validFixtureDataProvider(): \Generator
    {
        $repository = new HarFileRepository(__DIR__.'/../../../fixtures');

        foreach ($repository->loadMultiple() as $id => $har) {
            yield [$id];
        }

        yield ['edge-cases/minimal-valid.har'];
    }

    public function testValidateJsonWithValidMinimalHar(): void
    {
        $json = <<<'JSON'
{
  "log": {
    "version": "1.2",
    "creator": {
      "name": "test",
      "version": "1.0"
    },
    "entries": []
  }
}
JSON;

        $result = $this->validator->validateJson($json);

        $this->assertTrue($result->isValid());
    }

    public function testValidateJsonWithInvalidJson(): void
    {
        $json = '{this is not valid JSON}';

        $result = $this->validator->validateJson($json);

        $this->assertFalse($result->isValid());
        $this->assertSame(1, $result->getErrorCount());
        $this->assertStringContainsString('Invalid JSON', $result->getErrors()[0]->getMessage());
    }

    public function testValidateJsonWithMissingLog(): void
    {
        $json = '{"notLog": {"version": "1.2"}}';

        $result = $this->validator->validateJson($json);

        $this->assertFalse($result->isValid());
        $this->assertSame(1, $result->getErrorCount());
        $this->assertStringContainsString('log', $result->getErrors()[0]->getMessage());
    }

    public function testValidateJsonWithNonObjectRoot(): void
    {
        $json = '"just a string"';

        $result = $this->validator->validateJson($json);

        $this->assertFalse($result->isValid());
        $this->assertStringContainsString('JSON object', $result->getErrors()[0]->getMessage());
    }

    public function testValidateJsonWithMissingVersion(): void
    {
        $json = '{"log": {"creator": {"name": "test", "version": "1.0"}, "entries": []}}';

        $result = $this->validator->validateJson($json);

        $this->assertFalse($result->isValid());
        $this->assertStringContainsString('version', $result->getErrors()[0]->getMessage());
        $this->assertSame('log', $result->getErrors()[0]->getPath());
    }

    public function testValidateJsonWithMissingCreator(): void
    {
        $json = '{"log": {"version": "1.2", "entries": []}}';

        $result = $this->validator->validateJson($json);

        $this->assertFalse($result->isValid());
        $this->assertStringContainsString('creator', $result->getErrors()[0]->getMessage());
        $this->assertSame('log', $result->getErrors()[0]->getPath());
    }

    public function testValidateJsonWithMissingEntries(): void
    {
        $json = '{"log": {"version": "1.2", "creator": {"name": "test", "version": "1.0"}}}';

        $result = $this->validator->validateJson($json);

        $this->assertFalse($result->isValid());
        $this->assertStringContainsString('entries', $result->getErrors()[0]->getMessage());
    }

    public function testValidateJsonWithMissingCreatorName(): void
    {
        $json = '{"log": {"version": "1.2", "creator": {"version": "1.0"}, "entries": []}}';

        $result = $this->validator->validateJson($json);

        $this->assertFalse($result->isValid());
        $this->assertStringContainsString('name', $result->getErrors()[0]->getMessage());
        $this->assertSame('log.creator', $result->getErrors()[0]->getPath());
    }

    public function testValidateJsonWithMissingCreatorVersion(): void
    {
        $json = '{"log": {"version": "1.2", "creator": {"name": "test"}, "entries": []}}';

        $result = $this->validator->validateJson($json);

        $this->assertFalse($result->isValid());
        $this->assertStringContainsString('version', $result->getErrors()[0]->getMessage());
        $this->assertSame('log.creator', $result->getErrors()[0]->getPath());
    }

    public function testValidateJsonWithMultipleErrors(): void
    {
        $json = '{"log": {"entries": []}}';

        $result = $this->validator->validateJson($json);

        $this->assertFalse($result->isValid());
        $this->assertGreaterThanOrEqual(2, $result->getErrorCount());
    }

    public function testValidateJsonWithBom(): void
    {
        $json = pack('CCC', 0xEF, 0xBB, 0xBF).'{"log": {"version": "1.2", "creator": {"name": "test", "version": "1.0"}, "entries": []}}';

        $result = $this->validator->validateJson($json);

        $this->assertTrue($result->isValid());
    }

    public function testValidateWithCompleteEntry(): void
    {
        $har = $this->createHarWithCompleteEntry();

        $result = $this->validator->validate($har);

        $this->assertTrue($result->isValid());
    }

    public function testValidateWithInvalidPageref(): void
    {
        $har = $this->createHarWithInvalidPageref();

        $result = $this->validator->validate($har);

        $this->assertFalse($result->isValid());
        $this->assertStringContainsString('pageref', $result->getErrors()[0]->getMessage());
        $this->assertStringContainsString('nonexistent_page', $result->getErrors()[0]->getMessage());
        $this->assertSame('log.entries[0]', $result->getErrors()[0]->getPath());
    }

    public function testValidateWithValidPageref(): void
    {
        $har = $this->createHarWithValidPageref();

        $result = $this->validator->validate($har);

        $this->assertTrue($result->isValid());
    }

    public function testValidateWithCacheState(): void
    {
        $har = $this->createHarWithCacheState();

        $result = $this->validator->validate($har);

        $this->assertTrue($result->isValid());
    }

    public function testValidateEntryWithNegativeTime(): void
    {
        $har = $this->createMinimalValidHar();
        $entry = $this->createMinimalEntry();
        $entry->setTime(-1);
        $har->getLog()->setEntries([$entry]);

        $result = $this->validator->validate($har);

        $this->assertFalse($result->isValid());
        $this->assertStringContainsString('time', $result->getErrors()[0]->getMessage());
        $this->assertStringContainsString('non-negative', $result->getErrors()[0]->getMessage());
        $this->assertSame('log.entries[0]', $result->getErrors()[0]->getPath());
    }

    public function testValidateWithPostData(): void
    {
        $har = $this->createHarWithPostData();

        $result = $this->validator->validate($har);

        $this->assertTrue($result->isValid());
    }

    public function testValidateMultipleEntriesWithCorrectPaths(): void
    {
        $har = $this->createMinimalValidHar();
        $entry0 = $this->createMinimalEntry();
        $entry1 = $this->createMinimalEntry();
        $entry1->setTime(-5);

        $har->getLog()->setEntries([$entry0, $entry1]);

        $result = $this->validator->validate($har);

        $this->assertFalse($result->isValid());
        $this->assertSame('log.entries[1]', $result->getErrors()[0]->getPath());
    }

    public function testValidateCreatorPathInError(): void
    {
        $har = $this->createMinimalValidHar();
        $creator = new Creator();
        $creator->setVersion('1.0');

        $reflection = new \ReflectionProperty(Log::class, 'creator');
        $reflection->setValue($har->getLog(), $creator);

        $result = $this->validator->validate($har);

        $this->assertFalse($result->isValid());
        $this->assertSame('log.creator', $result->getErrors()[0]->getPath());
    }

    public function testValidateBrowserValidation(): void
    {
        $har = $this->createMinimalValidHar();
        $browser = (new Browser())
            ->setName('TestBrowser')
            ->setVersion('1.0');
        $har->getLog()->setBrowser($browser);

        $result = $this->validator->validate($har);

        $this->assertTrue($result->isValid());
    }

    public function testValidateBrowserWithMissingName(): void
    {
        $har = $this->createMinimalValidHar();
        $browser = new Browser();
        $browser->setVersion('1.0');

        $reflection = new \ReflectionProperty(Log::class, 'browser');
        $reflection->setValue($har->getLog(), $browser);

        $result = $this->validator->validate($har);

        $this->assertFalse($result->isValid());
        $this->assertSame('log.browser', $result->getErrors()[0]->getPath());
    }

    public function testValidatePagePathsAreCorrect(): void
    {
        $har = $this->createMinimalValidHar();

        $page = new Page();
        $page->setStartedDateTime(new \DateTime());
        $page->setId('page_1');

        $reflection = new \ReflectionProperty(Page::class, 'pageTimings');
        $reflection->setValue($page, new PageTimings());

        $har->getLog()->setPages([$page]);

        $result = $this->validator->validate($har);

        $this->assertFalse($result->isValid());
        $this->assertSame('log.pages[0]', $result->getErrors()[0]->getPath());
    }

    public function testValidateRequestPathsAreCorrect(): void
    {
        $har = $this->createMinimalValidHar();
        $entry = $this->createMinimalEntry();

        $request = new Request();
        $request->setUrl(new Uri('https://example.com'));
        $request->setHttpVersion('HTTP/1.1');
        $request->setCookies([]);
        $request->setHeaders([]);
        $request->setHeadersSize(0);
        $request->setBodySize(0);

        $reflection = new \ReflectionProperty(Entry::class, 'request');
        $reflection->setValue($entry, $request);

        $har->getLog()->setEntries([$entry]);

        $result = $this->validator->validate($har);

        $this->assertFalse($result->isValid());
        $this->assertSame('log.entries[0].request', $result->getErrors()[0]->getPath());
    }

    public function testValidateResponsePathsAreCorrect(): void
    {
        $har = $this->createMinimalValidHar();
        $entry = $this->createMinimalEntry();

        $response = new Response();
        $response->setStatusText('OK');
        $response->setHttpVersion('HTTP/1.1');
        $response->setCookies([]);
        $response->setHeaders([]);
        $response->setContent((new Content())->setSize(0)->setMimeType('text/html'));
        $response->setRedirectURL(new Uri(''));
        $response->setHeadersSize(0);
        $response->setBodySize(0);

        $reflection = new \ReflectionProperty(Entry::class, 'response');
        $reflection->setValue($entry, $response);

        $har->getLog()->setEntries([$entry]);

        $result = $this->validator->validate($har);

        $this->assertFalse($result->isValid());
        $this->assertSame('log.entries[0].response', $result->getErrors()[0]->getPath());
    }

    public function testValidateCookiePathsAreCorrect(): void
    {
        $har = $this->createMinimalValidHar();
        $entry = $this->createMinimalEntry();

        $cookie = new Cookie();
        $cookie->setValue('value');

        $reflection = new \ReflectionProperty(Request::class, 'cookies');
        $reflection->setValue($entry->getRequest(), [$cookie]);

        $har->getLog()->setEntries([$entry]);

        $result = $this->validator->validate($har);

        $this->assertFalse($result->isValid());
        $this->assertSame('log.entries[0].request.cookies[0]', $result->getErrors()[0]->getPath());
    }

    public function testValidateHeaderPathsAreCorrect(): void
    {
        $har = $this->createMinimalValidHar();
        $entry = $this->createMinimalEntry();

        $header = new Header();

        $reflection = new \ReflectionProperty(Request::class, 'headers');
        $reflection->setValue($entry->getRequest(), [$header]);

        $har->getLog()->setEntries([$entry]);

        $result = $this->validator->validate($har);

        $this->assertFalse($result->isValid());
        $this->assertSame('log.entries[0].request.headers[0]', $result->getErrors()[0]->getPath());
    }

    public function testValidateQueryStringPathsAreCorrect(): void
    {
        $har = $this->createMinimalValidHar();
        $entry = $this->createMinimalEntry();

        $param = new Params();
        $param->setValue('value');

        $entry->getRequest()->setQueryString([$param]);
        $har->getLog()->setEntries([$entry]);

        $result = $this->validator->validate($har);

        $this->assertFalse($result->isValid());
        $this->assertSame('log.entries[0].request.queryString[0]', $result->getErrors()[0]->getPath());
    }

    public function testValidateTimingsPathsAreCorrect(): void
    {
        $har = $this->createMinimalValidHar();
        $entry = $this->createMinimalEntry();

        $timings = new Timings();
        $timings->setWait(10);
        $timings->setReceive(5);

        $reflection = new \ReflectionProperty(Entry::class, 'timings');
        $reflection->setValue($entry, $timings);

        $har->getLog()->setEntries([$entry]);

        $result = $this->validator->validate($har);

        $this->assertFalse($result->isValid());
        $this->assertSame('log.entries[0].timings', $result->getErrors()[0]->getPath());
    }

    public function testValidateCacheStatePathsAreCorrect(): void
    {
        $har = $this->createMinimalValidHar();
        $entry = $this->createMinimalEntry();

        $cacheState = new CacheState();
        $cacheState->setETag('"abc"');
        $cacheState->setHitCount(1);

        $cache = (new Cache())->setBeforeRequest($cacheState);

        $reflection = new \ReflectionProperty(Entry::class, 'cache');
        $reflection->setValue($entry, $cache);

        $har->getLog()->setEntries([$entry]);

        $result = $this->validator->validate($har);

        $this->assertFalse($result->isValid());
        $this->assertSame('log.entries[0].cache.beforeRequest', $result->getErrors()[0]->getPath());
    }

    public function testValidateAfterRequestCacheStatePath(): void
    {
        $har = $this->createMinimalValidHar();
        $entry = $this->createMinimalEntry();

        $cacheState = new CacheState();
        $cacheState->setLastAccess((new \DateTime())->format(Log::ISO_8601_MICROSECONDS));
        $cacheState->setHitCount(1);

        $cache = (new Cache())->setAfterRequest($cacheState);

        $reflection = new \ReflectionProperty(Entry::class, 'cache');
        $reflection->setValue($entry, $cache);

        $har->getLog()->setEntries([$entry]);

        $result = $this->validator->validate($har);

        $this->assertFalse($result->isValid());
        $this->assertSame('log.entries[0].cache.afterRequest', $result->getErrors()[0]->getPath());
    }

    public function testValidateJsonStructuralErrorsReturnEarly(): void
    {
        $json = '{"log": {"comment": "just a comment"}}';

        $result = $this->validator->validateJson($json);

        $this->assertFalse($result->isValid());
        $errors = $result->getErrors();

        $errorMessages = array_map(fn ($e) => $e->getMessage(), $errors);
        $combinedMessages = implode(' ', $errorMessages);

        $this->assertStringContainsString('version', $combinedMessages);
        $this->assertStringContainsString('creator', $combinedMessages);
        $this->assertStringContainsString('entries', $combinedMessages);
    }

    public function testValidateJsonStructuralErrorsDoNotDeserialize(): void
    {
        // When structural errors exist, the HAR should NOT be deserialized
        // This tests that we return early with structural errors
        $json = '{"log": {"version": "1.2"}}'; // Missing creator and entries

        $result = $this->validator->validateJson($json);

        $this->assertFalse($result->isValid());
        $this->assertGreaterThanOrEqual(2, $result->getErrorCount());

        // Check we have both missing creator and entries errors
        $errorMessages = array_map(fn ($e) => $e->getMessage(), $result->getErrors());
        $this->assertTrue(
            (bool) array_filter($errorMessages, fn ($m) => str_contains($m, 'creator')),
            'Should have creator error'
        );
        $this->assertTrue(
            (bool) array_filter($errorMessages, fn ($m) => str_contains($m, 'entries')),
            'Should have entries error'
        );
    }

    public function testValidateMultipleEntriesAccumulateErrors(): void
    {
        $har = $this->createMinimalValidHar();
        $entry0 = $this->createMinimalEntry();
        $entry0->setTime(-1); // Invalid
        $entry1 = $this->createMinimalEntry();
        $entry1->setTime(-2); // Also invalid

        $har->getLog()->setEntries([$entry0, $entry1]);

        $result = $this->validator->validate($har);

        $this->assertFalse($result->isValid());
        // Must have at least 2 errors (one per entry)
        $this->assertGreaterThanOrEqual(2, $result->getErrorCount());

        // Check both entries have errors
        $paths = array_map(fn ($e) => $e->getPath(), $result->getErrors());
        $this->assertContains('log.entries[0]', $paths);
        $this->assertContains('log.entries[1]', $paths);
    }

    public function testValidateMultiplePagesAccumulateErrors(): void
    {
        $har = $this->createMinimalValidHar();

        // Create two invalid pages (missing title)
        $page0 = new Page();
        $page0->setStartedDateTime(new \DateTime());
        $page0->setId('page_0');
        $reflection = new \ReflectionProperty(Page::class, 'pageTimings');
        $reflection->setValue($page0, new PageTimings());

        $page1 = new Page();
        $page1->setStartedDateTime(new \DateTime());
        $page1->setId('page_1');
        $reflection->setValue($page1, new PageTimings());

        $har->getLog()->setPages([$page0, $page1]);

        $result = $this->validator->validate($har);

        $this->assertFalse($result->isValid());
        // Must have at least 2 errors (one per page)
        $this->assertGreaterThanOrEqual(2, $result->getErrorCount());

        $paths = array_map(fn ($e) => $e->getPath(), $result->getErrors());
        $this->assertContains('log.pages[0]', $paths);
        $this->assertContains('log.pages[1]', $paths);
    }

    public function testValidatePageTimingsPath(): void
    {
        $har = $this->createMinimalValidHar();

        $page = (new Page())
            ->setId('page_1')
            ->setTitle('Test Page')
            ->setStartedDateTime(new \DateTime())
            ->setPageTimings(new PageTimings());

        $har->getLog()->setPages([$page]);

        $result = $this->validator->validate($har);

        // PageTimings has no required fields, so this should be valid
        $this->assertTrue($result->isValid());
    }

    public function testValidateBrowserErrorsAccumulate(): void
    {
        $har = $this->createMinimalValidHar();

        // Browser missing both name and version
        $browser = new Browser();
        $reflection = new \ReflectionProperty(Log::class, 'browser');
        $reflection->setValue($har->getLog(), $browser);

        $result = $this->validator->validate($har);

        $this->assertFalse($result->isValid());
        // Should have at least 2 errors (name and version)
        $this->assertGreaterThanOrEqual(2, $result->getErrorCount());
    }

    public function testValidateCreatorErrorsAccumulate(): void
    {
        $har = $this->createMinimalValidHar();

        // Creator missing both name and version
        $creator = new Creator();
        $reflection = new \ReflectionProperty(Log::class, 'creator');
        $reflection->setValue($har->getLog(), $creator);

        $result = $this->validator->validate($har);

        $this->assertFalse($result->isValid());
        // Should have at least 2 errors (name and version)
        $this->assertGreaterThanOrEqual(2, $result->getErrorCount());

        $paths = array_map(fn ($e) => $e->getPath(), $result->getErrors());
        // All should be at log.creator path
        foreach ($paths as $path) {
            $this->assertSame('log.creator', $path);
        }
    }

    public function testValidateLogErrorsPlusCreatorErrorsAccumulate(): void
    {
        // Test that errors from validateLog AND validateCreator are accumulated
        // This kills the UnwrapArrayMerge mutation on the creator validation line
        $har = new Har();
        $log = new Log();
        // Missing version (validateLog error)
        // Creator missing name (validateCreator error)
        $creator = new Creator();
        $creator->setVersion('1.0');

        $reflection = new \ReflectionProperty(Log::class, 'creator');
        $reflection->setValue($log, $creator);

        $log->setEntries([]);
        $reflection = new \ReflectionProperty(Har::class, 'log');
        $reflection->setValue($har, $log);

        $result = $this->validator->validate($har);

        $this->assertFalse($result->isValid());
        // Should have at least 2 errors: log.version AND log.creator.name
        $this->assertGreaterThanOrEqual(2, $result->getErrorCount());

        $paths = array_map(fn ($e) => $e->getPath(), $result->getErrors());
        $this->assertContains('log', $paths, 'Should have error at log path for missing version');
        $this->assertContains('log.creator', $paths, 'Should have error at log.creator path for missing name');
    }

    public function testValidateLogErrorsPlusBrowserErrorsAccumulate(): void
    {
        // Test that errors from validateLog AND validateCreator (for browser) are accumulated
        $har = $this->createMinimalValidHar();

        // Add invalid browser (missing name)
        $browser = new Browser();
        $browser->setVersion('1.0');
        $reflection = new \ReflectionProperty(Log::class, 'browser');
        $reflection->setValue($har->getLog(), $browser);

        // Also make creator invalid (missing version)
        $creator = new Creator();
        $creator->setName('test');
        $reflection = new \ReflectionProperty(Log::class, 'creator');
        $reflection->setValue($har->getLog(), $creator);

        $result = $this->validator->validate($har);

        $this->assertFalse($result->isValid());
        // Should have at least 2 errors: creator.version AND browser.name
        $this->assertGreaterThanOrEqual(2, $result->getErrorCount());

        $paths = array_map(fn ($e) => $e->getPath(), $result->getErrors());
        $this->assertContains('log.creator', $paths, 'Should have error at log.creator');
        $this->assertContains('log.browser', $paths, 'Should have error at log.browser');
    }

    public function testValidateLogErrorsPlusEntryErrorsAccumulate(): void
    {
        // Test that errors before entry validation AND entry errors are accumulated
        $har = $this->createMinimalValidHar();

        // Make creator invalid
        $creator = new Creator();
        $creator->setName('test');
        $reflection = new \ReflectionProperty(Log::class, 'creator');
        $reflection->setValue($har->getLog(), $creator);

        // Add entry with invalid time
        $entry = $this->createMinimalEntry();
        $entry->setTime(-1);
        $har->getLog()->setEntries([$entry]);

        $result = $this->validator->validate($har);

        $this->assertFalse($result->isValid());
        $this->assertGreaterThanOrEqual(2, $result->getErrorCount());

        $paths = array_map(fn ($e) => $e->getPath(), $result->getErrors());
        $this->assertContains('log.creator', $paths, 'Should have creator error');
        $this->assertContains('log.entries[0]', $paths, 'Should have entry error');
    }

    public function testValidateLogErrorsPlusPageErrorsAccumulate(): void
    {
        // Test that errors before page validation AND page errors are accumulated
        $har = $this->createMinimalValidHar();

        // Make creator invalid
        $creator = new Creator();
        $creator->setName('test');
        $reflection = new \ReflectionProperty(Log::class, 'creator');
        $reflection->setValue($har->getLog(), $creator);

        // Add invalid page (missing title)
        $page = new Page();
        $page->setStartedDateTime(new \DateTime());
        $page->setId('page_1');
        $pageTimingsReflection = new \ReflectionProperty(Page::class, 'pageTimings');
        $pageTimingsReflection->setValue($page, new PageTimings());
        $har->getLog()->setPages([$page]);

        $result = $this->validator->validate($har);

        $this->assertFalse($result->isValid());
        $this->assertGreaterThanOrEqual(2, $result->getErrorCount());

        $paths = array_map(fn ($e) => $e->getPath(), $result->getErrors());
        $this->assertContains('log.creator', $paths, 'Should have creator error');
        $this->assertContains('log.pages[0]', $paths, 'Should have page error');
    }

    public function testValidatePageErrorsPlusPagerefErrorsAccumulate(): void
    {
        // Test that page errors AND pageref errors are accumulated
        $har = $this->createMinimalValidHar();

        // Add page missing title (page error)
        $page = new Page();
        $page->setStartedDateTime(new \DateTime());
        $page->setId('page_1');
        $pageTimingsReflection = new \ReflectionProperty(Page::class, 'pageTimings');
        $pageTimingsReflection->setValue($page, new PageTimings());
        $har->getLog()->setPages([$page]);

        // Add entry with invalid pageref
        $entry = $this->createMinimalEntry();
        $entry->setPageref('invalid_page');
        $har->getLog()->setEntries([$entry]);

        $result = $this->validator->validate($har);

        $this->assertFalse($result->isValid());
        $this->assertGreaterThanOrEqual(2, $result->getErrorCount());

        $paths = array_map(fn ($e) => $e->getPath(), $result->getErrors());
        $this->assertContains('log.pages[0]', $paths, 'Should have page error for missing title');
        $this->assertContains('log.entries[0]', $paths, 'Should have entry error for invalid pageref');
    }

    public function testValidateJsonWithValidStructureThenDeserializationFails(): void
    {
        // JSON has valid structure but deserialization fails
        $json = '{"log": {"version": "1.2", "creator": {"name": "test", "version": "1.0"}, "entries": "not an array"}}';

        $result = $this->validator->validateJson($json);

        $this->assertFalse($result->isValid());
        $this->assertStringContainsString('entries', $result->getErrors()[0]->getMessage());
    }

    public function testValidateEntryErrorsPlusRequestErrorsAccumulate(): void
    {
        // Tests validateEntry array_merge with validateRequest errors
        $har = $this->createMinimalValidHar();
        $entry = $this->createMinimalEntry();

        // Entry has invalid time (-1)
        $entry->setTime(-1);

        // Request missing method
        $request = new Request();
        $request->setUrl(new Uri('https://example.com'));
        $request->setHttpVersion('HTTP/1.1');
        $request->setCookies([]);
        $request->setHeaders([]);
        $request->setHeadersSize(0);
        $request->setBodySize(0);

        $reflection = new \ReflectionProperty(Entry::class, 'request');
        $reflection->setValue($entry, $request);

        $har->getLog()->setEntries([$entry]);
        $result = $this->validator->validate($har);

        $this->assertFalse($result->isValid());
        $this->assertGreaterThanOrEqual(2, $result->getErrorCount());

        $paths = array_map(fn ($e) => $e->getPath(), $result->getErrors());
        $this->assertContains('log.entries[0]', $paths, 'Should have entry time error');
        $this->assertContains('log.entries[0].request', $paths, 'Should have request method error');
    }

    public function testValidateEntryErrorsPlusResponseErrorsAccumulate(): void
    {
        // Tests validateEntry array_merge with validateResponse errors
        $har = $this->createMinimalValidHar();
        $entry = $this->createMinimalEntry();

        // Entry has invalid time
        $entry->setTime(-1);

        // Response missing status
        $response = new Response();
        $response->setStatusText('OK');
        $response->setHttpVersion('HTTP/1.1');
        $response->setCookies([]);
        $response->setHeaders([]);
        $response->setContent((new Content())->setSize(0)->setMimeType('text/html'));
        $response->setRedirectURL(new Uri(''));
        $response->setHeadersSize(0);
        $response->setBodySize(0);

        $reflection = new \ReflectionProperty(Entry::class, 'response');
        $reflection->setValue($entry, $response);

        $har->getLog()->setEntries([$entry]);
        $result = $this->validator->validate($har);

        $this->assertFalse($result->isValid());
        $this->assertGreaterThanOrEqual(2, $result->getErrorCount());

        $paths = array_map(fn ($e) => $e->getPath(), $result->getErrors());
        $this->assertContains('log.entries[0]', $paths, 'Should have entry time error');
        $this->assertContains('log.entries[0].response', $paths, 'Should have response status error');
    }

    public function testValidateEntryErrorsPlusCacheErrorsAccumulate(): void
    {
        // Tests validateEntry array_merge with validateCache errors
        $har = $this->createMinimalValidHar();
        $entry = $this->createMinimalEntry();

        // Entry has invalid time
        $entry->setTime(-1);

        // Cache with invalid beforeRequest (missing lastAccess)
        $cacheState = new CacheState();
        $cacheState->setETag('"abc"');
        $cacheState->setHitCount(1);
        $cache = (new Cache())->setBeforeRequest($cacheState);

        $reflection = new \ReflectionProperty(Entry::class, 'cache');
        $reflection->setValue($entry, $cache);

        $har->getLog()->setEntries([$entry]);
        $result = $this->validator->validate($har);

        $this->assertFalse($result->isValid());
        $this->assertGreaterThanOrEqual(2, $result->getErrorCount());

        $paths = array_map(fn ($e) => $e->getPath(), $result->getErrors());
        $this->assertContains('log.entries[0]', $paths, 'Should have entry time error');
        $this->assertContains('log.entries[0].cache.beforeRequest', $paths, 'Should have cache error');
    }

    public function testValidateEntryErrorsPlusTimingsErrorsAccumulate(): void
    {
        // Tests validateEntry array_merge with validateTimings errors
        $har = $this->createMinimalValidHar();
        $entry = $this->createMinimalEntry();

        // Entry has invalid time
        $entry->setTime(-1);

        // Timings missing send
        $timings = new Timings();
        $timings->setWait(10);
        $timings->setReceive(5);

        $reflection = new \ReflectionProperty(Entry::class, 'timings');
        $reflection->setValue($entry, $timings);

        $har->getLog()->setEntries([$entry]);
        $result = $this->validator->validate($har);

        $this->assertFalse($result->isValid());
        $this->assertGreaterThanOrEqual(2, $result->getErrorCount());

        $paths = array_map(fn ($e) => $e->getPath(), $result->getErrors());
        $this->assertContains('log.entries[0]', $paths, 'Should have entry time error');
        $this->assertContains('log.entries[0].timings', $paths, 'Should have timings error');
    }

    public function testValidateRequestErrorsPlusCookieErrorsAccumulate(): void
    {
        // Tests validateRequest array_merge with validateCookie errors
        $har = $this->createMinimalValidHar();
        $entry = $this->createMinimalEntry();

        // Request missing method (request error)
        // Cookie missing name (cookie error)
        $request = new Request();
        $request->setUrl(new Uri('https://example.com'));
        $request->setHttpVersion('HTTP/1.1');
        $cookie = new Cookie();
        $cookie->setValue('value');
        $request->setCookies([$cookie]);
        $request->setHeaders([]);
        $request->setHeadersSize(0);
        $request->setBodySize(0);

        $reflection = new \ReflectionProperty(Entry::class, 'request');
        $reflection->setValue($entry, $request);

        $har->getLog()->setEntries([$entry]);
        $result = $this->validator->validate($har);

        $this->assertFalse($result->isValid());
        $this->assertGreaterThanOrEqual(2, $result->getErrorCount());

        $paths = array_map(fn ($e) => $e->getPath(), $result->getErrors());
        $this->assertContains('log.entries[0].request', $paths, 'Should have request method error');
        $this->assertContains('log.entries[0].request.cookies[0]', $paths, 'Should have cookie name error');
    }

    public function testValidateRequestErrorsPlusHeaderErrorsAccumulate(): void
    {
        // Tests validateRequest array_merge with validateHeader errors
        $har = $this->createMinimalValidHar();
        $entry = $this->createMinimalEntry();

        // Request missing method (request error)
        // Header missing name (header error)
        $request = new Request();
        $request->setUrl(new Uri('https://example.com'));
        $request->setHttpVersion('HTTP/1.1');
        $request->setCookies([]);
        $header = new Header();
        $header->setValue('value');
        $reflection = new \ReflectionProperty(Request::class, 'headers');
        $reflection->setValue($request, [$header]);
        $request->setHeadersSize(0);
        $request->setBodySize(0);

        $reflectionEntry = new \ReflectionProperty(Entry::class, 'request');
        $reflectionEntry->setValue($entry, $request);

        $har->getLog()->setEntries([$entry]);
        $result = $this->validator->validate($har);

        $this->assertFalse($result->isValid());
        $this->assertGreaterThanOrEqual(2, $result->getErrorCount());

        $paths = array_map(fn ($e) => $e->getPath(), $result->getErrors());
        $this->assertContains('log.entries[0].request', $paths, 'Should have request method error');
        $this->assertContains('log.entries[0].request.headers[0]', $paths, 'Should have header name error');
    }

    public function testValidateRequestErrorsPlusQueryStringErrorsAccumulate(): void
    {
        // Tests validateRequest array_merge with validateParams errors for queryString
        $har = $this->createMinimalValidHar();
        $entry = $this->createMinimalEntry();

        // Request missing method (request error)
        // QueryString param missing name (param error)
        $request = new Request();
        $request->setUrl(new Uri('https://example.com'));
        $request->setHttpVersion('HTTP/1.1');
        $request->setCookies([]);
        $request->setHeaders([]);
        $param = new Params();
        $param->setValue('value');
        $request->setQueryString([$param]);
        $request->setHeadersSize(0);
        $request->setBodySize(0);

        $reflectionEntry = new \ReflectionProperty(Entry::class, 'request');
        $reflectionEntry->setValue($entry, $request);

        $har->getLog()->setEntries([$entry]);
        $result = $this->validator->validate($har);

        $this->assertFalse($result->isValid());
        $this->assertGreaterThanOrEqual(2, $result->getErrorCount());

        $paths = array_map(fn ($e) => $e->getPath(), $result->getErrors());
        $this->assertContains('log.entries[0].request', $paths, 'Should have request method error');
        $this->assertContains('log.entries[0].request.queryString[0]', $paths, 'Should have queryString param error');
    }

    public function testValidateRequestWithPostDataPresent(): void
    {
        // Tests that postData is validated when present
        // Note: getMimeType() returns empty string by default (doesn't throw), so
        // we verify the code path runs without error
        $har = $this->createMinimalValidHar();
        $entry = $this->createMinimalEntry();

        // Valid request with postData that has mimeType set
        $postData = new PostData();
        $postData->setMimeType('application/json');
        $postData->setText('{"key": "value"}');
        $entry->getRequest()->setPostData($postData);

        $har->getLog()->setEntries([$entry]);
        $result = $this->validator->validate($har);

        $this->assertTrue($result->isValid());
    }

    public function testValidateResponseErrorsPlusContentErrorsAccumulate(): void
    {
        // Tests validateResponse array_merge with validateContent errors
        $har = $this->createMinimalValidHar();
        $entry = $this->createMinimalEntry();

        // Response missing status (response error)
        // Content missing size (content error)
        $content = new Content();
        $content->setMimeType('text/html');

        $response = new Response();
        $response->setStatusText('OK');
        $response->setHttpVersion('HTTP/1.1');
        $response->setCookies([]);
        $response->setHeaders([]);
        $reflectionContent = new \ReflectionProperty(Response::class, 'content');
        $reflectionContent->setValue($response, $content);
        $response->setRedirectURL(new Uri(''));
        $response->setHeadersSize(0);
        $response->setBodySize(0);

        $reflection = new \ReflectionProperty(Entry::class, 'response');
        $reflection->setValue($entry, $response);

        $har->getLog()->setEntries([$entry]);
        $result = $this->validator->validate($har);

        $this->assertFalse($result->isValid());
        $this->assertGreaterThanOrEqual(2, $result->getErrorCount());

        $paths = array_map(fn ($e) => $e->getPath(), $result->getErrors());
        $this->assertContains('log.entries[0].response', $paths, 'Should have response status error');
        $this->assertContains('log.entries[0].response.content', $paths, 'Should have content size error');
    }

    public function testValidateCacheBeforeAndAfterRequestErrorsAccumulate(): void
    {
        // Tests validateCache array_merge with both beforeRequest and afterRequest errors
        $har = $this->createMinimalValidHar();
        $entry = $this->createMinimalEntry();

        // Cache with invalid beforeRequest AND invalid afterRequest
        $beforeState = new CacheState();
        $beforeState->setETag('"abc"');
        $beforeState->setHitCount(1);
        // Missing lastAccess

        $afterState = new CacheState();
        $afterState->setLastAccess((new \DateTime())->format(Log::ISO_8601_MICROSECONDS));
        $afterState->setHitCount(1);
        // Missing eTag

        $cache = (new Cache())
            ->setBeforeRequest($beforeState)
            ->setAfterRequest($afterState);

        $reflection = new \ReflectionProperty(Entry::class, 'cache');
        $reflection->setValue($entry, $cache);

        $har->getLog()->setEntries([$entry]);
        $result = $this->validator->validate($har);

        $this->assertFalse($result->isValid());
        $this->assertGreaterThanOrEqual(2, $result->getErrorCount());

        $paths = array_map(fn ($e) => $e->getPath(), $result->getErrors());
        $this->assertContains('log.entries[0].cache.beforeRequest', $paths, 'Should have beforeRequest error');
        $this->assertContains('log.entries[0].cache.afterRequest', $paths, 'Should have afterRequest error');
    }

    public function testValidateJsonStructuralErrorsPreventDeserialization(): void
    {
        // Test that when structural errors exist, we return early (the NotIdentical mutation)
        // By verifying we get the expected structural errors without deserialization error
        $json = '{"log": {"version": "1.2", "creator": {"name": "test"}}}'; // Missing creator.version and entries

        $result = $this->validator->validateJson($json);

        $this->assertFalse($result->isValid());
        // Should have structural errors only, not deserialization errors
        $errorMessages = array_map(fn ($e) => $e->getMessage(), $result->getErrors());
        $combinedMessages = implode(' ', $errorMessages);

        $this->assertStringContainsString('version', $combinedMessages);
        $this->assertStringContainsString('entries', $combinedMessages);
        // Should NOT contain "Failed to parse HAR" which would be a deserialization error
        $this->assertStringNotContainsString('Failed to parse', $combinedMessages);
    }

    public function testValidateJsonLogAccumulatesMultipleErrors(): void
    {
        // Test that validateJsonLog accumulates creator errors with other errors
        $json = '{"log": {"creator": {}}}'; // Missing version, entries, creator.name, creator.version

        $result = $this->validator->validateJson($json);

        $this->assertFalse($result->isValid());
        $this->assertGreaterThanOrEqual(4, $result->getErrorCount());
    }

    public function testValidateEntryTimeZeroIsValid(): void
    {
        // Test that time=0 is valid (not just time >= 0)
        // This kills the LessThan mutation that changes < 0 to <= 0
        $har = $this->createMinimalValidHar();
        $entry = $this->createMinimalEntry();
        $entry->setTime(0); // Zero is valid

        $har->getLog()->setEntries([$entry]);

        $result = $this->validator->validate($har);

        $this->assertTrue($result->isValid());
    }

    public function testValidateResponseCookiesWithMultipleInvalidCookies(): void
    {
        // Test that validateResponse accumulates errors from multiple invalid cookies
        // This kills Foreach_ and path concat mutations
        $har = $this->createMinimalValidHar();
        $entry = $this->createMinimalEntry();

        // Create two invalid cookies (missing name)
        $cookie0 = new Cookie();
        $cookie0->setValue('value0');
        $cookie1 = new Cookie();
        $cookie1->setValue('value1');

        $reflection = new \ReflectionProperty(Response::class, 'cookies');
        $reflection->setValue($entry->getResponse(), [$cookie0, $cookie1]);

        $har->getLog()->setEntries([$entry]);
        $result = $this->validator->validate($har);

        $this->assertFalse($result->isValid());
        $this->assertGreaterThanOrEqual(2, $result->getErrorCount());

        $paths = array_map(fn ($e) => $e->getPath(), $result->getErrors());
        $this->assertContains('log.entries[0].response.cookies[0]', $paths);
        $this->assertContains('log.entries[0].response.cookies[1]', $paths);
    }

    public function testValidateResponseHeadersWithMultipleInvalidHeaders(): void
    {
        // Test that validateResponse accumulates errors from multiple invalid headers
        // This kills Foreach_ and path concat mutations
        $har = $this->createMinimalValidHar();
        $entry = $this->createMinimalEntry();

        // Create two invalid headers (missing name)
        $header0 = new Header();
        $header0->setValue('value0');
        $header1 = new Header();
        $header1->setValue('value1');

        $reflection = new \ReflectionProperty(Response::class, 'headers');
        $reflection->setValue($entry->getResponse(), [$header0, $header1]);

        $har->getLog()->setEntries([$entry]);
        $result = $this->validator->validate($har);

        $this->assertFalse($result->isValid());
        $this->assertGreaterThanOrEqual(2, $result->getErrorCount());

        $paths = array_map(fn ($e) => $e->getPath(), $result->getErrors());
        $this->assertContains('log.entries[0].response.headers[0]', $paths);
        $this->assertContains('log.entries[0].response.headers[1]', $paths);
    }

    public function testValidatePageWithMultipleErrors(): void
    {
        // Test that validatePage returns multiple errors (kills ArrayOneItem mutation)
        $har = $this->createMinimalValidHar();

        // Page missing all required fields except pageTimings
        $page = new Page();
        $pageTimingsReflection = new \ReflectionProperty(Page::class, 'pageTimings');
        $pageTimingsReflection->setValue($page, new PageTimings());

        $har->getLog()->setPages([$page]);

        $result = $this->validator->validate($har);

        $this->assertFalse($result->isValid());
        // Should have 3 errors: startedDateTime, id, title
        $this->assertGreaterThanOrEqual(3, $result->getErrorCount());

        $paths = array_map(fn ($e) => $e->getPath(), $result->getErrors());
        // All errors should be at log.pages[0] path
        $pagesErrors = array_filter($paths, fn ($p) => 'log.pages[0]' === $p);
        $this->assertGreaterThanOrEqual(3, \count($pagesErrors));
    }

    public function testValidateResponseCookiesPathIsCorrect(): void
    {
        // Ensure path includes the full path, not just partial
        $har = $this->createMinimalValidHar();
        $entry = $this->createMinimalEntry();

        $cookie = new Cookie();
        $cookie->setValue('value');

        $reflection = new \ReflectionProperty(Response::class, 'cookies');
        $reflection->setValue($entry->getResponse(), [$cookie]);

        $har->getLog()->setEntries([$entry]);
        $result = $this->validator->validate($har);

        $this->assertFalse($result->isValid());
        // Verify the full path is correct
        $paths = array_map(fn ($e) => $e->getPath(), $result->getErrors());
        $this->assertContains('log.entries[0].response.cookies[0]', $paths);
        // Path should NOT be just '.cookies[0]' or missing index
        $this->assertNotContains('.cookies[0]', $paths);
        $this->assertNotContains('log.entries[0].response.cookies[]', $paths);
    }

    public function testValidateResponseHeadersPathIsCorrect(): void
    {
        // Ensure path includes the full path with correct index
        $har = $this->createMinimalValidHar();
        $entry = $this->createMinimalEntry();

        $header = new Header();
        $header->setValue('value');

        $reflection = new \ReflectionProperty(Response::class, 'headers');
        $reflection->setValue($entry->getResponse(), [$header]);

        $har->getLog()->setEntries([$entry]);
        $result = $this->validator->validate($har);

        $this->assertFalse($result->isValid());
        $paths = array_map(fn ($e) => $e->getPath(), $result->getErrors());
        $this->assertContains('log.entries[0].response.headers[0]', $paths);
    }

    public function testValidateResponseContentPathIsCorrect(): void
    {
        // Ensure content validation path is correct
        $har = $this->createMinimalValidHar();
        $entry = $this->createMinimalEntry();

        $content = new Content();
        // Missing size and mimeType
        $reflectionContent = new \ReflectionProperty(Response::class, 'content');
        $reflectionContent->setValue($entry->getResponse(), $content);

        $har->getLog()->setEntries([$entry]);
        $result = $this->validator->validate($har);

        $this->assertFalse($result->isValid());
        $paths = array_map(fn ($e) => $e->getPath(), $result->getErrors());
        $this->assertContains('log.entries[0].response.content', $paths);
    }

    public function testValidateTimingsWaitZeroIsValid(): void
    {
        // Test that wait=0 is valid (kills LessThan mutation)
        $har = $this->createMinimalValidHar();
        $entry = $this->createMinimalEntry();

        $timings = (new Timings())
            ->setSend(0)
            ->setWait(0) // Zero is valid
            ->setReceive(0);

        $reflection = new \ReflectionProperty(Entry::class, 'timings');
        $reflection->setValue($entry, $timings);

        $har->getLog()->setEntries([$entry]);
        $result = $this->validator->validate($har);

        $this->assertTrue($result->isValid());
    }

    public function testValidateCacheBeforeRequestErrorsPlusAfterRequestErrorsAccumulate(): void
    {
        // Test that errors from beforeRequest and afterRequest are accumulated
        // This kills the UnwrapArrayMerge mutation on cache validation
        $har = $this->createMinimalValidHar();
        $entry = $this->createMinimalEntry();

        // Both beforeRequest and afterRequest have errors (lastAccess throws when unset)
        $beforeState = new CacheState();
        // Missing lastAccess (throws)

        $afterState = new CacheState();
        // Missing lastAccess (throws)

        $cache = (new Cache())
            ->setBeforeRequest($beforeState)
            ->setAfterRequest($afterState);

        $reflection = new \ReflectionProperty(Entry::class, 'cache');
        $reflection->setValue($entry, $cache);

        $har->getLog()->setEntries([$entry]);
        $result = $this->validator->validate($har);

        $this->assertFalse($result->isValid());
        // Should have at least 2 errors (1 from beforeRequest + 1 from afterRequest)
        $this->assertGreaterThanOrEqual(2, $result->getErrorCount());

        $paths = array_map(fn ($e) => $e->getPath(), $result->getErrors());
        $beforeRequestErrors = array_filter($paths, fn ($p) => str_contains($p, 'beforeRequest'));
        $afterRequestErrors = array_filter($paths, fn ($p) => str_contains($p, 'afterRequest'));

        $this->assertGreaterThanOrEqual(1, \count($beforeRequestErrors), 'Should have beforeRequest errors');
        $this->assertGreaterThanOrEqual(1, \count($afterRequestErrors), 'Should have afterRequest errors');
    }

    public function testValidateEntryWithMultipleInvalidPagerefs(): void
    {
        $har = $this->createMinimalValidHar();

        $page = (new Page())
            ->setId('page_1')
            ->setTitle('Test Page')
            ->setStartedDateTime(new \DateTime())
            ->setPageTimings(new PageTimings());

        $har->getLog()->setPages([$page]);

        $entry0 = $this->createMinimalEntry();
        $entry0->setPageref('invalid_0');

        $entry1 = $this->createMinimalEntry();
        $entry1->setPageref('invalid_1');

        $har->getLog()->setEntries([$entry0, $entry1]);

        $result = $this->validator->validate($har);

        $this->assertFalse($result->isValid());
        // Should have 2 pageref errors
        $this->assertGreaterThanOrEqual(2, $result->getErrorCount());

        $errors = $result->getErrors();
        $this->assertStringContainsString('invalid_0', $errors[0]->getMessage());
        $this->assertStringContainsString('invalid_1', $errors[1]->getMessage());
    }

    private function createMinimalValidHar(): Har
    {
        $creator = (new Creator())
            ->setName('test')
            ->setVersion('1.0');

        $log = (new Log())
            ->setVersion('1.2')
            ->setCreator($creator)
            ->setEntries([]);

        return (new Har())->setLog($log);
    }

    private function createMinimalEntry(): Entry
    {
        $request = (new Request())
            ->setMethod('GET')
            ->setUrl(new Uri('https://example.com'))
            ->setHttpVersion('HTTP/1.1')
            ->setCookies([])
            ->setHeaders([])
            ->setQueryString([])
            ->setHeadersSize(0)
            ->setBodySize(0);

        $content = (new Content())
            ->setSize(0)
            ->setMimeType('text/html');

        $response = (new Response())
            ->setStatus(200)
            ->setStatusText('OK')
            ->setHttpVersion('HTTP/1.1')
            ->setCookies([])
            ->setHeaders([])
            ->setContent($content)
            ->setRedirectURL(new Uri(''))
            ->setHeadersSize(0)
            ->setBodySize(0);

        $timings = (new Timings())
            ->setSend(0)
            ->setWait(10)
            ->setReceive(5);

        return (new Entry())
            ->setStartedDateTime(new \DateTime())
            ->setTime(15)
            ->setRequest($request)
            ->setResponse($response)
            ->setCache(new Cache())
            ->setTimings($timings);
    }

    private function createHarWithCompleteEntry(): Har
    {
        $har = $this->createMinimalValidHar();
        $har->getLog()->setEntries([$this->createMinimalEntry()]);

        return $har;
    }

    private function createHarWithInvalidPageref(): Har
    {
        $har = $this->createMinimalValidHar();

        $page = (new Page())
            ->setId('page_1')
            ->setTitle('Test Page')
            ->setStartedDateTime(new \DateTime())
            ->setPageTimings(new PageTimings());

        $har->getLog()->setPages([$page]);

        $entry = $this->createMinimalEntry();
        $entry->setPageref('nonexistent_page');

        $har->getLog()->setEntries([$entry]);

        return $har;
    }

    private function createHarWithValidPageref(): Har
    {
        $har = $this->createMinimalValidHar();

        $page = (new Page())
            ->setId('page_1')
            ->setTitle('Test Page')
            ->setStartedDateTime(new \DateTime())
            ->setPageTimings(new PageTimings());

        $har->getLog()->setPages([$page]);

        $entry = $this->createMinimalEntry();
        $entry->setPageref('page_1');

        $har->getLog()->setEntries([$entry]);

        return $har;
    }

    private function createHarWithCacheState(): Har
    {
        $har = $this->createMinimalValidHar();

        $cacheState = (new CacheState())
            ->setLastAccess((new \DateTime())->format(Log::ISO_8601_MICROSECONDS))
            ->setETag('"abc123"')
            ->setHitCount(5);

        $cache = (new Cache())
            ->setAfterRequest($cacheState);

        $entry = $this->createMinimalEntry();
        $reflection = new \ReflectionProperty(Entry::class, 'cache');
        $reflection->setValue($entry, $cache);

        $har->getLog()->setEntries([$entry]);

        return $har;
    }

    private function createHarWithPostData(): Har
    {
        $har = $this->createMinimalValidHar();

        $postData = (new PostData())
            ->setMimeType('application/x-www-form-urlencoded')
            ->setText('key=value');

        $entry = $this->createMinimalEntry();
        $entry->getRequest()->setPostData($postData);
        $entry->getRequest()->setMethod('POST');

        $har->getLog()->setEntries([$entry]);

        return $har;
    }
}
