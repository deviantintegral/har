<?php

declare(strict_types=1);

namespace Deviantintegral\Har\Tests\Unit;

use Deviantintegral\Har\Cache;
use Deviantintegral\Har\Content;
use Deviantintegral\Har\Cookie;
use Deviantintegral\Har\Creator;
use Deviantintegral\Har\Entry;
use Deviantintegral\Har\Har;
use Deviantintegral\Har\HarSanitizer;
use Deviantintegral\Har\Header;
use Deviantintegral\Har\Log;
use Deviantintegral\Har\Params;
use Deviantintegral\Har\PostData;
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

    public function testRedactQueryParams(): void
    {
        $har = $this->createHarWithQueryParams([
            'api_key' => 'secret-key',
            'token' => 'auth-token',
            'page' => '1',
        ]);

        $sanitizer = new HarSanitizer();
        $sanitizer->redactQueryParams(['api_key', 'token']);

        $sanitized = $sanitizer->sanitize($har);

        $params = $sanitized->getLog()->getEntries()[0]->getRequest()->getQueryString();
        $paramMap = $this->paramsToMap($params);

        $this->assertEquals('[REDACTED]', $paramMap['api_key']);
        $this->assertEquals('[REDACTED]', $paramMap['token']);
        $this->assertEquals('1', $paramMap['page']);
    }

    public function testRedactQueryParamsCaseInsensitive(): void
    {
        $har = $this->createHarWithQueryParams([
            'API_KEY' => 'secret-key',
            'Token' => 'auth-token',
        ]);

        $sanitizer = new HarSanitizer();
        $sanitizer->redactQueryParams(['api_key', 'token']);

        $sanitized = $sanitizer->sanitize($har);

        $params = $sanitized->getLog()->getEntries()[0]->getRequest()->getQueryString();
        $paramMap = $this->paramsToMap($params);

        $this->assertEquals('[REDACTED]', $paramMap['API_KEY']);
        $this->assertEquals('[REDACTED]', $paramMap['Token']);
    }

    public function testRedactQueryParamsFluentInterface(): void
    {
        $sanitizer = new HarSanitizer();

        $result = $sanitizer->redactQueryParams(['api_key']);

        $this->assertSame($sanitizer, $result);
    }

    public function testRedactBodyFieldsFormEncoded(): void
    {
        $har = $this->createHarWithPostParams([
            'username' => 'john',
            'password' => 'secret123',
            'remember' => 'true',
        ]);

        $sanitizer = new HarSanitizer();
        $sanitizer->redactBodyFields(['password']);

        $sanitized = $sanitizer->sanitize($har);

        $params = $sanitized->getLog()->getEntries()[0]->getRequest()->getPostData()->getParams();
        $paramMap = $this->paramsToMap($params);

        $this->assertEquals('[REDACTED]', $paramMap['password']);
        $this->assertEquals('john', $paramMap['username']);
        $this->assertEquals('true', $paramMap['remember']);
    }

    public function testRedactBodyFieldsJsonRequest(): void
    {
        $json = json_encode([
            'username' => 'john',
            'password' => 'secret123',
            'data' => ['nested' => 'value'],
        ]);

        $har = $this->createHarWithJsonPostData($json);

        $sanitizer = new HarSanitizer();
        $sanitizer->redactBodyFields(['password']);

        $sanitized = $sanitizer->sanitize($har);

        $text = $sanitized->getLog()->getEntries()[0]->getRequest()->getPostData()->getText();
        $data = json_decode($text, true);

        $this->assertEquals('[REDACTED]', $data['password']);
        $this->assertEquals('john', $data['username']);
        $this->assertEquals(['nested' => 'value'], $data['data']);
    }

    public function testRedactBodyFieldsJsonResponse(): void
    {
        $json = json_encode([
            'user' => 'john',
            'token' => 'secret-token',
            'expires' => 3600,
        ]);

        $har = $this->createHarWithJsonResponse($json);

        $sanitizer = new HarSanitizer();
        $sanitizer->redactBodyFields(['token']);

        $sanitized = $sanitizer->sanitize($har);

        $text = $sanitized->getLog()->getEntries()[0]->getResponse()->getContent()->getText();
        $data = json_decode($text, true);

        $this->assertEquals('[REDACTED]', $data['token']);
        $this->assertEquals('john', $data['user']);
        $this->assertEquals(3600, $data['expires']);
    }

    public function testRedactBodyFieldsNestedJson(): void
    {
        $json = json_encode([
            'user' => [
                'name' => 'john',
                'credentials' => [
                    'password' => 'secret123',
                    'api_key' => 'key123',
                ],
            ],
            'password' => 'top-level-password',
        ]);

        $har = $this->createHarWithJsonPostData($json);

        $sanitizer = new HarSanitizer();
        $sanitizer->redactBodyFields(['password', 'api_key']);

        $sanitized = $sanitizer->sanitize($har);

        $text = $sanitized->getLog()->getEntries()[0]->getRequest()->getPostData()->getText();
        $data = json_decode($text, true);

        $this->assertEquals('[REDACTED]', $data['password']);
        $this->assertEquals('[REDACTED]', $data['user']['credentials']['password']);
        $this->assertEquals('[REDACTED]', $data['user']['credentials']['api_key']);
        $this->assertEquals('john', $data['user']['name']);
    }

    public function testRedactBodyFieldsCaseInsensitive(): void
    {
        $json = json_encode([
            'PASSWORD' => 'secret1',
            'Password' => 'secret2',
        ]);

        $har = $this->createHarWithJsonPostData($json);

        $sanitizer = new HarSanitizer();
        $sanitizer->redactBodyFields(['password']);

        $sanitized = $sanitizer->sanitize($har);

        $text = $sanitized->getLog()->getEntries()[0]->getRequest()->getPostData()->getText();
        $data = json_decode($text, true);

        $this->assertEquals('[REDACTED]', $data['PASSWORD']);
        $this->assertEquals('[REDACTED]', $data['Password']);
    }

    public function testRedactBodyFieldsFluentInterface(): void
    {
        $sanitizer = new HarSanitizer();

        $result = $sanitizer->redactBodyFields(['password']);

        $this->assertSame($sanitizer, $result);
    }

    public function testRedactBodyFieldsNonJsonContentUnchanged(): void
    {
        $har = $this->createHarWithTextResponse('plain text content');

        $sanitizer = new HarSanitizer();
        $sanitizer->redactBodyFields(['password']);

        $sanitized = $sanitizer->sanitize($har);

        $text = $sanitized->getLog()->getEntries()[0]->getResponse()->getContent()->getText();
        $this->assertEquals('plain text content', $text);
    }

    public function testRedactBodyFieldsInvalidJsonUnchanged(): void
    {
        $postData = (new PostData())
            ->setMimeType('application/json')
            ->setText('not valid json {');

        $request = (new Request())
            ->setMethod('POST')
            ->setUrl(new Uri('https://example.com'))
            ->setHeaders([])
            ->setHttpVersion('HTTP/1.1')
            ->setPostData($postData);

        $har = $this->createHarWithRequest($request);

        $sanitizer = new HarSanitizer();
        $sanitizer->redactBodyFields(['password']);

        $sanitized = $sanitizer->sanitize($har);

        $text = $sanitized->getLog()->getEntries()[0]->getRequest()->getPostData()->getText();
        $this->assertEquals('not valid json {', $text);
    }

    public function testRedactBodyFieldsEmptyJsonText(): void
    {
        // Test with empty string JSON body - covers line 294
        $postData = (new PostData())
            ->setMimeType('application/json')
            ->setText('');

        $request = (new Request())
            ->setMethod('POST')
            ->setUrl(new Uri('https://example.com'))
            ->setHeaders([])
            ->setHttpVersion('HTTP/1.1')
            ->setPostData($postData);

        $har = $this->createHarWithRequest($request);

        $sanitizer = new HarSanitizer();
        $sanitizer->redactBodyFields(['password']);

        $sanitized = $sanitizer->sanitize($har);

        // Empty text should remain empty (sanitizeJsonText returns null, setText not called)
        $this->assertEquals('', $sanitized->getLog()->getEntries()[0]->getRequest()->getPostData()->getText());
    }

    public function testRedactBodyFieldsJsonPrimitiveValue(): void
    {
        // Test with JSON primitive (string) - covers line 317 (non-array data)
        $json = json_encode('just a string');

        $har = $this->createHarWithJsonResponse($json);

        $sanitizer = new HarSanitizer();
        $sanitizer->redactBodyFields(['password']);

        $sanitized = $sanitizer->sanitize($har);

        $text = $sanitized->getLog()->getEntries()[0]->getResponse()->getContent()->getText();
        // Primitive should be unchanged
        $this->assertEquals('"just a string"', $text);
    }

    public function testRedactBodyFieldsJsonArray(): void
    {
        $json = json_encode([
            ['id' => 1, 'password' => 'secret1'],
            ['id' => 2, 'password' => 'secret2'],
        ]);

        $har = $this->createHarWithJsonResponse($json);

        $sanitizer = new HarSanitizer();
        $sanitizer->redactBodyFields(['password']);

        $sanitized = $sanitizer->sanitize($har);

        $text = $sanitized->getLog()->getEntries()[0]->getResponse()->getContent()->getText();
        $data = json_decode($text, true);

        $this->assertEquals('[REDACTED]', $data[0]['password']);
        $this->assertEquals('[REDACTED]', $data[1]['password']);
        $this->assertEquals(1, $data[0]['id']);
        $this->assertEquals(2, $data[1]['id']);
    }

    public function testRedactBodyFieldsJsonMimeTypeWithNoText(): void
    {
        // Create content with JSON mime type but no text
        $content = (new Content())
            ->setSize(0)
            ->setCompression(0)
            ->setMimeType('application/json');

        $response = (new Response())
            ->setStatus(200)
            ->setStatusText('OK')
            ->setHeaders([])
            ->setHttpVersion('HTTP/1.1')
            ->setContent($content)
            ->setRedirectURL(new Uri(''));

        $har = $this->createHarWithResponse($response);

        $sanitizer = new HarSanitizer();
        $sanitizer->redactBodyFields(['password']);

        // Should not throw - nothing to sanitize
        $sanitized = $sanitizer->sanitize($har);

        // Content should not have text
        $this->assertFalse($sanitized->getLog()->getEntries()[0]->getResponse()->getContent()->hasText());
    }

    public function testRedactBodyFieldsPostDataJsonMimeTypeWithNoText(): void
    {
        // Create postData with JSON mime type but using params instead of text
        $postData = (new PostData())
            ->setMimeType('application/json')
            ->setParams([(new Params())->setName('password')->setValue('secret')]);

        $request = (new Request())
            ->setMethod('POST')
            ->setUrl(new Uri('https://example.com'))
            ->setHeaders([])
            ->setHttpVersion('HTTP/1.1')
            ->setPostData($postData);

        $har = $this->createHarWithRequest($request);

        $sanitizer = new HarSanitizer();
        $sanitizer->redactBodyFields(['password']);

        $sanitized = $sanitizer->sanitize($har);

        // Params should be redacted, but no JSON text processing should occur
        $params = $sanitized->getLog()->getEntries()[0]->getRequest()->getPostData()->getParams();
        $paramMap = $this->paramsToMap($params);

        $this->assertEquals('[REDACTED]', $paramMap['password']);
    }

    public function testRedactBodyFieldsJsonPreservesSlashesAndUnicode(): void
    {
        // Test that slashes and unicode are preserved (not escaped)
        $json = json_encode([
            'url' => 'https://example.com/path',
            'name' => 'ユーザー',
            'password' => 'secret',
        ], \JSON_UNESCAPED_SLASHES | \JSON_UNESCAPED_UNICODE);

        $har = $this->createHarWithJsonPostData($json);

        $sanitizer = new HarSanitizer();
        $sanitizer->redactBodyFields(['password']);

        $sanitized = $sanitizer->sanitize($har);

        $text = $sanitized->getLog()->getEntries()[0]->getRequest()->getPostData()->getText();

        // Verify URL slashes are not escaped
        $this->assertStringContainsString('https://example.com/path', $text);
        // Verify unicode is not escaped
        $this->assertStringContainsString('ユーザー', $text);
        // Verify password is redacted
        $data = json_decode($text, true);
        $this->assertEquals('[REDACTED]', $data['password']);
    }

    public function testRedactCookies(): void
    {
        $har = $this->createHarWithRequestCookies([
            'session_id' => 'abc123',
            'tracking' => 'xyz789',
            'preferences' => 'dark_mode',
        ]);

        $sanitizer = new HarSanitizer();
        $sanitizer->redactCookies(['session_id', 'tracking']);

        $sanitized = $sanitizer->sanitize($har);

        $cookies = $sanitized->getLog()->getEntries()[0]->getRequest()->getCookies();
        $cookieMap = $this->cookiesToMap($cookies);

        $this->assertEquals('[REDACTED]', $cookieMap['session_id']);
        $this->assertEquals('[REDACTED]', $cookieMap['tracking']);
        $this->assertEquals('dark_mode', $cookieMap['preferences']);
    }

    public function testRedactResponseCookies(): void
    {
        $har = $this->createHarWithResponseCookies([
            'session_id' => 'secret-session',
            'auth_token' => 'secret-token',
            'locale' => 'en_US',
        ]);

        $sanitizer = new HarSanitizer();
        $sanitizer->redactCookies(['session_id', 'auth_token']);

        $sanitized = $sanitizer->sanitize($har);

        $cookies = $sanitized->getLog()->getEntries()[0]->getResponse()->getCookies();
        $cookieMap = $this->cookiesToMap($cookies);

        $this->assertEquals('[REDACTED]', $cookieMap['session_id']);
        $this->assertEquals('[REDACTED]', $cookieMap['auth_token']);
        $this->assertEquals('en_US', $cookieMap['locale']);
    }

    public function testRedactCookiesCaseInsensitive(): void
    {
        $har = $this->createHarWithRequestCookies([
            'SESSION_ID' => 'secret1',
            'Session_Id' => 'secret2',
        ]);

        $sanitizer = new HarSanitizer();
        $sanitizer->redactCookies(['session_id']);

        $sanitized = $sanitizer->sanitize($har);

        $cookies = $sanitized->getLog()->getEntries()[0]->getRequest()->getCookies();
        $cookieMap = $this->cookiesToMap($cookies);

        $this->assertEquals('[REDACTED]', $cookieMap['SESSION_ID']);
        $this->assertEquals('[REDACTED]', $cookieMap['Session_Id']);
    }

    public function testRedactCookiesFluentInterface(): void
    {
        $sanitizer = new HarSanitizer();

        $result = $sanitizer->redactCookies(['session_id']);

        $this->assertSame($sanitizer, $result);
    }

    public function testRedactCookiesOriginalUnmodified(): void
    {
        $har = $this->createHarWithRequestCookies([
            'session_id' => 'original-value',
        ]);

        $originalValue = $har->getLog()->getEntries()[0]->getRequest()->getCookies()[0]->getValue();

        $sanitizer = new HarSanitizer();
        $sanitizer->redactCookies(['session_id']);
        $sanitizer->sanitize($har);

        // Original should be unchanged
        $currentValue = $har->getLog()->getEntries()[0]->getRequest()->getCookies()[0]->getValue();
        $this->assertEquals($originalValue, $currentValue);
        $this->assertEquals('original-value', $currentValue);
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

    /**
     * @param array<string, string> $params
     */
    private function createHarWithQueryParams(array $params): Har
    {
        $paramObjects = [];
        foreach ($params as $name => $value) {
            $param = (new Params())->setName($name)->setValue($value);
            $paramObjects[] = $param;
        }

        $request = (new Request())
            ->setMethod('GET')
            ->setUrl(new Uri('https://example.com'))
            ->setQueryString($paramObjects)
            ->setHeaders([])
            ->setHttpVersion('HTTP/1.1');

        return $this->createHarWithRequest($request);
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

    /**
     * @param Params[] $params
     *
     * @return array<string, string>
     */
    private function paramsToMap(array $params): array
    {
        $map = [];
        foreach ($params as $param) {
            $map[$param->getName()] = $param->getValue();
        }

        return $map;
    }

    /**
     * @param array<string, string> $params
     */
    private function createHarWithPostParams(array $params): Har
    {
        $paramObjects = [];
        foreach ($params as $name => $value) {
            $param = (new Params())->setName($name)->setValue($value);
            $paramObjects[] = $param;
        }

        $postData = (new PostData())
            ->setMimeType('application/x-www-form-urlencoded')
            ->setParams($paramObjects);

        $request = (new Request())
            ->setMethod('POST')
            ->setUrl(new Uri('https://example.com'))
            ->setHeaders([])
            ->setHttpVersion('HTTP/1.1')
            ->setPostData($postData);

        return $this->createHarWithRequest($request);
    }

    private function createHarWithJsonPostData(string $json): Har
    {
        $postData = (new PostData())
            ->setMimeType('application/json')
            ->setText($json);

        $request = (new Request())
            ->setMethod('POST')
            ->setUrl(new Uri('https://example.com'))
            ->setHeaders([])
            ->setHttpVersion('HTTP/1.1')
            ->setPostData($postData);

        return $this->createHarWithRequest($request);
    }

    private function createHarWithJsonResponse(string $json): Har
    {
        $content = (new Content())
            ->setSize(\strlen($json))
            ->setCompression(0)
            ->setMimeType('application/json')
            ->setText($json);

        $response = (new Response())
            ->setStatus(200)
            ->setStatusText('OK')
            ->setHeaders([])
            ->setHttpVersion('HTTP/1.1')
            ->setContent($content)
            ->setRedirectURL(new Uri(''));

        return $this->createHarWithResponse($response);
    }

    private function createHarWithTextResponse(string $text): Har
    {
        $content = (new Content())
            ->setSize(\strlen($text))
            ->setCompression(0)
            ->setMimeType('text/plain')
            ->setText($text);

        $response = (new Response())
            ->setStatus(200)
            ->setStatusText('OK')
            ->setHeaders([])
            ->setHttpVersion('HTTP/1.1')
            ->setContent($content)
            ->setRedirectURL(new Uri(''));

        return $this->createHarWithResponse($response);
    }

    /**
     * @param array<string, string> $cookies
     */
    private function createHarWithRequestCookies(array $cookies): Har
    {
        $cookieObjects = [];
        foreach ($cookies as $name => $value) {
            $cookie = (new Cookie())->setName($name)->setValue($value);
            $cookieObjects[] = $cookie;
        }

        $request = (new Request())
            ->setMethod('GET')
            ->setUrl(new Uri('https://example.com'))
            ->setHeaders([])
            ->setCookies($cookieObjects)
            ->setHttpVersion('HTTP/1.1');

        return $this->createHarWithRequest($request);
    }

    /**
     * @param array<string, string> $cookies
     */
    private function createHarWithResponseCookies(array $cookies): Har
    {
        $cookieObjects = [];
        foreach ($cookies as $name => $value) {
            $cookie = (new Cookie())->setName($name)->setValue($value);
            $cookieObjects[] = $cookie;
        }

        $content = (new Content())
            ->setSize(0)
            ->setCompression(0);

        $response = (new Response())
            ->setStatus(200)
            ->setStatusText('OK')
            ->setHeaders([])
            ->setCookies($cookieObjects)
            ->setHttpVersion('HTTP/1.1')
            ->setContent($content)
            ->setRedirectURL(new Uri(''));

        return $this->createHarWithResponse($response);
    }

    /**
     * @param Cookie[] $cookies
     *
     * @return array<string, string>
     */
    private function cookiesToMap(array $cookies): array
    {
        $map = [];
        foreach ($cookies as $cookie) {
            $map[$cookie->getName()] = $cookie->getValue();
        }

        return $map;
    }
}
