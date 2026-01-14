<?php

declare(strict_types=1);

namespace Deviantintegral\Har\Tests\Unit;

use Deviantintegral\Har\Cache;
use Deviantintegral\Har\Content;
use Deviantintegral\Har\Creator;
use Deviantintegral\Har\Entry;
use Deviantintegral\Har\Har;
use Deviantintegral\Har\HarSanitizer;
use Deviantintegral\Har\Header;
use Deviantintegral\Har\Log;
use Deviantintegral\Har\Request;
use Deviantintegral\Har\Response;
use Deviantintegral\Har\Timings;
use GuzzleHttp\Psr7\Uri;

/**
 * @covers \Deviantintegral\Har\HarSanitizer
 */
class HarSanitizerTest extends HarTestBase
{
    public function testRedactHeaders(): void
    {
        $har = $this->createHarWithHeaders([
            'Authorization' => 'Bearer secret-token',
            'Content-Type' => 'application/json',
            'Cookie' => 'session=abc123',
        ]);

        $sanitizer = new HarSanitizer();
        $sanitizer->redactHeaders(['Authorization', 'Cookie']);

        $sanitized = $sanitizer->sanitize($har);

        $headers = $sanitized->getLog()->getEntries()[0]->getRequest()->getHeaders();
        $headerMap = $this->headersToMap($headers);

        $this->assertEquals('[REDACTED]', $headerMap['Authorization']);
        $this->assertEquals('application/json', $headerMap['Content-Type']);
        $this->assertEquals('[REDACTED]', $headerMap['Cookie']);
    }

    public function testRedactHeadersCaseInsensitive(): void
    {
        $har = $this->createHarWithHeaders([
            'authorization' => 'Bearer secret-token',
            'COOKIE' => 'session=abc123',
        ]);

        $sanitizer = new HarSanitizer();
        $sanitizer->redactHeaders(['Authorization', 'Cookie']);

        $sanitized = $sanitizer->sanitize($har);

        $headers = $sanitized->getLog()->getEntries()[0]->getRequest()->getHeaders();
        $headerMap = $this->headersToMap($headers);

        $this->assertEquals('[REDACTED]', $headerMap['authorization']);
        $this->assertEquals('[REDACTED]', $headerMap['COOKIE']);
    }

    public function testRedactHeadersCaseSensitive(): void
    {
        $har = $this->createHarWithHeaders([
            'authorization' => 'Bearer secret-token',
            'Authorization' => 'Bearer another-token',
        ]);

        $sanitizer = new HarSanitizer();
        $sanitizer->redactHeaders(['Authorization']);
        $sanitizer->setCaseSensitive(true);

        $sanitized = $sanitizer->sanitize($har);

        $headers = $sanitized->getLog()->getEntries()[0]->getRequest()->getHeaders();
        $headerMap = $this->headersToMap($headers);

        // Only exact case match should be redacted
        $this->assertEquals('Bearer secret-token', $headerMap['authorization']);
        $this->assertEquals('[REDACTED]', $headerMap['Authorization']);
    }

    public function testRedactResponseHeaders(): void
    {
        $har = $this->createHarWithResponseHeaders([
            'Set-Cookie' => 'session=secret; HttpOnly',
            'Content-Type' => 'application/json',
        ]);

        $sanitizer = new HarSanitizer();
        $sanitizer->redactHeaders(['Set-Cookie']);

        $sanitized = $sanitizer->sanitize($har);

        $headers = $sanitized->getLog()->getEntries()[0]->getResponse()->getHeaders();
        $headerMap = $this->headersToMap($headers);

        $this->assertEquals('[REDACTED]', $headerMap['Set-Cookie']);
        $this->assertEquals('application/json', $headerMap['Content-Type']);
    }

    public function testCustomRedactedValue(): void
    {
        $har = $this->createHarWithHeaders([
            'Authorization' => 'Bearer secret-token',
        ]);

        $sanitizer = new HarSanitizer();
        $sanitizer->redactHeaders(['Authorization']);
        $sanitizer->setRedactedValue('***');

        $sanitized = $sanitizer->sanitize($har);

        $headers = $sanitized->getLog()->getEntries()[0]->getRequest()->getHeaders();
        $headerMap = $this->headersToMap($headers);

        $this->assertEquals('***', $headerMap['Authorization']);
    }

    public function testOriginalHarIsNotModified(): void
    {
        $har = $this->createHarWithHeaders([
            'Authorization' => 'Bearer secret-token',
        ]);

        $originalValue = $har->getLog()->getEntries()[0]->getRequest()->getHeaders()[0]->getValue();

        $sanitizer = new HarSanitizer();
        $sanitizer->redactHeaders(['Authorization']);
        $sanitizer->sanitize($har);

        // Original should be unchanged
        $currentValue = $har->getLog()->getEntries()[0]->getRequest()->getHeaders()[0]->getValue();
        $this->assertEquals($originalValue, $currentValue);
        $this->assertEquals('Bearer secret-token', $currentValue);
    }

    public function testNoRedactionConfigured(): void
    {
        $har = $this->createHarWithHeaders([
            'Authorization' => 'Bearer secret-token',
        ]);

        $sanitizer = new HarSanitizer();
        $sanitized = $sanitizer->sanitize($har);

        $headers = $sanitized->getLog()->getEntries()[0]->getRequest()->getHeaders();
        $headerMap = $this->headersToMap($headers);

        $this->assertEquals('Bearer secret-token', $headerMap['Authorization']);
    }

    public function testMultipleEntries(): void
    {
        $har = $this->createHarWithMultipleEntries();

        $sanitizer = new HarSanitizer();
        $sanitizer->redactHeaders(['Authorization']);

        $sanitized = $sanitizer->sanitize($har);

        $entries = $sanitized->getLog()->getEntries();
        $this->assertCount(2, $entries);

        foreach ($entries as $entry) {
            $headers = $this->headersToMap($entry->getRequest()->getHeaders());
            $this->assertEquals('[REDACTED]', $headers['Authorization']);
        }
    }

    public function testFluentInterface(): void
    {
        $sanitizer = new HarSanitizer();

        $result = $sanitizer
            ->redactHeaders(['Authorization'])
            ->setRedactedValue('***')
            ->setCaseSensitive(true);

        $this->assertSame($sanitizer, $result);
    }

    public function testHeadersSizeRecalculatedAfterRedaction(): void
    {
        // Create a HAR with a header that has a known value
        $har = $this->createHarWithHeaders([
            'Authorization' => 'Bearer very-long-secret-token-that-is-quite-lengthy',
        ]);

        $originalHeadersSize = $har->getLog()->getEntries()[0]->getRequest()->getHeadersSize();

        $sanitizer = new HarSanitizer();
        $sanitizer->redactHeaders(['Authorization']);

        $sanitized = $sanitizer->sanitize($har);

        $newHeadersSize = $sanitized->getLog()->getEntries()[0]->getRequest()->getHeadersSize();

        // The redacted value "[REDACTED]" is shorter than the original token
        // so the headers size should be smaller
        $this->assertLessThan($originalHeadersSize, $newHeadersSize);
    }

    public function testResponseHeadersSizeRecalculatedAfterRedaction(): void
    {
        // Create a HAR with a response header that has a known value
        $har = $this->createHarWithResponseHeaders([
            'Set-Cookie' => 'session=very-long-secret-session-id-that-should-be-redacted',
        ]);

        $originalHeadersSize = $har->getLog()->getEntries()[0]->getResponse()->getHeadersSize();

        $sanitizer = new HarSanitizer();
        $sanitizer->redactHeaders(['Set-Cookie']);

        $sanitized = $sanitizer->sanitize($har);

        $newHeadersSize = $sanitized->getLog()->getEntries()[0]->getResponse()->getHeadersSize();

        // The redacted value "[REDACTED]" is shorter than the original value
        $this->assertLessThan($originalHeadersSize, $newHeadersSize);
    }

    public function testHeadersSizeUnchangedWhenNoHeadersRedacted(): void
    {
        // Create a HAR with headers that won't be redacted
        $har = $this->createHarWithHeaders([
            'Content-Type' => 'application/json',
            'Accept' => '*/*',
        ]);

        $originalHeadersSize = $har->getLog()->getEntries()[0]->getRequest()->getHeadersSize();

        $sanitizer = new HarSanitizer();
        // Configure to redact headers that don't exist
        $sanitizer->redactHeaders(['Authorization', 'Cookie']);

        $sanitized = $sanitizer->sanitize($har);

        $newHeadersSize = $sanitized->getLog()->getEntries()[0]->getRequest()->getHeadersSize();

        // Headers size should be exactly the same since nothing was redacted
        $this->assertSame($originalHeadersSize, $newHeadersSize);
    }

    public function testWithRealFixture(): void
    {
        $repository = $this->getHarFileRepository();
        $har = $repository->load('www.softwareishard.com-single-entry.har');

        $sanitizer = new HarSanitizer();
        $sanitizer->redactHeaders(['Accept-Encoding', 'User-Agent']);

        $sanitized = $sanitizer->sanitize($har);

        $headers = $sanitized->getLog()->getEntries()[0]->getRequest()->getHeaders();
        $headerMap = $this->headersToMap($headers);

        // These headers should be redacted if they exist
        if (isset($headerMap['Accept-Encoding'])) {
            $this->assertEquals('[REDACTED]', $headerMap['Accept-Encoding']);
        }
        if (isset($headerMap['User-Agent'])) {
            $this->assertEquals('[REDACTED]', $headerMap['User-Agent']);
        }
    }

    /**
     * @param array<string, string> $headers
     */
    private function createHarWithHeaders(array $headers): Har
    {
        $headerObjects = [];
        foreach ($headers as $name => $value) {
            $header = (new Header())->setName($name)->setValue($value);
            $headerObjects[] = $header;
        }

        $request = (new Request())
            ->setMethod('GET')
            ->setUrl(new Uri('https://example.com'))
            ->setHeaders($headerObjects)
            ->setHttpVersion('HTTP/1.1');

        return $this->createHarWithRequest($request);
    }

    /**
     * @param array<string, string> $headers
     */
    private function createHarWithResponseHeaders(array $headers): Har
    {
        $headerObjects = [];
        foreach ($headers as $name => $value) {
            $header = (new Header())->setName($name)->setValue($value);
            $headerObjects[] = $header;
        }

        $content = (new Content())
            ->setSize(0)
            ->setCompression(0);

        $response = (new Response())
            ->setStatus(200)
            ->setStatusText('OK')
            ->setHeaders($headerObjects)
            ->setHttpVersion('HTTP/1.1')
            ->setContent($content)
            ->setRedirectURL(new Uri(''));

        return $this->createHarWithResponse($response);
    }

    private function createHarWithMultipleEntries(): Har
    {
        $entries = [];

        for ($i = 0; $i < 2; ++$i) {
            $headers = [
                (new Header())->setName('Authorization')->setValue('Bearer token-'.$i),
            ];

            $request = (new Request())
                ->setMethod('GET')
                ->setUrl(new Uri('https://example.com/api/'.$i))
                ->setHeaders($headers)
                ->setHttpVersion('HTTP/1.1');

            $content = (new Content())
                ->setSize(0)
                ->setCompression(0);

            $response = (new Response())
                ->setStatus(200)
                ->setStatusText('OK')
                ->setHeaders([])
                ->setHttpVersion('HTTP/1.1')
                ->setContent($content)
                ->setRedirectURL(new Uri(''));

            $timings = (new Timings())
                ->setBlocked(-1)
                ->setDns(-1)
                ->setConnect(-1)
                ->setSsl(-1)
                ->setSend(0)
                ->setWait(100)
                ->setReceive(10);

            $entry = (new Entry())
                ->setRequest($request)
                ->setResponse($response)
                ->setCache(new Cache())
                ->setTimings($timings)
                ->setTime(110);

            $entries[] = $entry;
        }

        $creator = (new Creator())
            ->setName('Test')
            ->setVersion('1.0');

        $log = (new Log())
            ->setVersion('1.2')
            ->setCreator($creator)
            ->setEntries($entries);

        return (new Har())->setLog($log);
    }

    private function createHarWithRequest(Request $request): Har
    {
        $content = (new Content())
            ->setSize(0)
            ->setCompression(0);

        $response = (new Response())
            ->setStatus(200)
            ->setStatusText('OK')
            ->setHeaders([])
            ->setHttpVersion('HTTP/1.1')
            ->setContent($content)
            ->setRedirectURL(new Uri(''));

        return $this->createHarWithRequestAndResponse($request, $response);
    }

    private function createHarWithResponse(Response $response): Har
    {
        $request = (new Request())
            ->setMethod('GET')
            ->setUrl(new Uri('https://example.com'))
            ->setHeaders([])
            ->setHttpVersion('HTTP/1.1');

        return $this->createHarWithRequestAndResponse($request, $response);
    }

    private function createHarWithRequestAndResponse(Request $request, Response $response): Har
    {
        $timings = (new Timings())
            ->setBlocked(-1)
            ->setDns(-1)
            ->setConnect(-1)
            ->setSsl(-1)
            ->setSend(0)
            ->setWait(100)
            ->setReceive(10);

        $entry = (new Entry())
            ->setRequest($request)
            ->setResponse($response)
            ->setCache(new Cache())
            ->setTimings($timings)
            ->setTime(110);

        $creator = (new Creator())
            ->setName('Test')
            ->setVersion('1.0');

        $log = (new Log())
            ->setVersion('1.2')
            ->setCreator($creator)
            ->setEntries([$entry]);

        return (new Har())->setLog($log);
    }

    /**
     * @param Header[] $headers
     *
     * @return array<string, string>
     */
    private function headersToMap(array $headers): array
    {
        $map = [];
        foreach ($headers as $header) {
            $map[$header->getName()] = $header->getValue();
        }

        return $map;
    }
}
