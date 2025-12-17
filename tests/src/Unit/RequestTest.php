<?php

declare(strict_types=1);

namespace Deviantintegral\Har\Tests\Unit;

use Deviantintegral\Har\Cookie;
use Deviantintegral\Har\Header;
use Deviantintegral\Har\PostData;
use Deviantintegral\Har\Request;
use GuzzleHttp\Psr7\Uri;

/**
 * @covers \Deviantintegral\Har\Request
 */
class RequestTest extends HarTestBase
{
    public function testSerialize(): void
    {
        $serializer = $this->getSerializer();

        $request = (new Request())
          ->setBodySize(0)
          ->setPostData(new PostData())
          ->setCookies([(new Cookie())->setPath('/')])
          ->setHeaders([(new Header())->setName('Test')->setValue('value')])
          ->setHeadersSize(0)
          ->setHttpVersion('2.0')
          ->setComment('Test comment');

        $serialized = $serializer->serialize($request, 'json');
        $this->assertEquals(
            [
                'httpVersion' => $request->getHttpVersion(),
                'cookies' => json_decode(
                    $serializer->serialize($request->getCookies(), 'json'),
                    true
                ),
                'headers' => json_decode(
                    $serializer->serialize($request->getHeaders(), 'json'),
                    true
                ),
                'postData' => json_decode(
                    $serializer->serialize($request->getPostData(), 'json'),
                    true
                ),
                'headersSize' => $request->getHeadersSize(),
                'bodySize' => $request->getBodySize(),
                'comment' => $request->getComment(),
            ],
            json_decode($serialized, true)
        );

        $this->assertDeserialize($serialized, Request::class, $request);
    }

    public function testFromPsr7(): void
    {
        $uri = new Uri('https://www.example.com');
        $psr7 = new \GuzzleHttp\Psr7\Request(
            'POST',
            $uri,
            ['Accept' => '*/*'],
            'body',
            '2.0'
        );
        $har_request = Request::fromPsr7Request($psr7);
        $this->assertEquals('POST', $har_request->getMethod());
        $this->assertEquals($uri, $har_request->getUrl());
        $this->assertEquals(
            [
                (new Header())->setName('Host')->setValue($uri->getHost()),
                (new Header())->setName('Accept')->setValue('*/*'),
            ],
            $har_request->getHeaders()
        );
        $this->assertEquals('body', $har_request->getPostData()->getText());
        $this->assertEquals(4, $har_request->getBodySize());
        $this->assertEquals('HTTP/2.0', $har_request->getHttpVersion());
    }

    public function testFromPsr7ServerRequest(): void
    {
        $uri = new Uri('https://www.example.com/path?foo=bar');
        $psr7 = new \GuzzleHttp\Psr7\ServerRequest(
            'POST',
            $uri,
            ['Accept' => '*/*', 'Cookie' => 'session=abc123'],
            'name=value',
            '1.1',
            [
                'REQUEST_METHOD' => 'POST',
                'REQUEST_URI' => '/path?foo=bar',
                'SERVER_NAME' => 'www.example.com',
            ]
        );
        $psr7 = $psr7->withCookieParams(['session' => 'abc123'])
            ->withQueryParams(['foo' => 'bar'])
            ->withParsedBody(['name' => 'value'])
            ->withAttribute('custom_attr', 'custom_value');

        $har_request = Request::fromPsr7ServerRequest($psr7);

        $this->assertEquals('POST', $har_request->getMethod());
        $this->assertEquals($uri, $har_request->getUrl());
        $this->assertEquals('HTTP/1.1', $har_request->getHttpVersion());

        // Verify headers
        $headers = $har_request->getHeaders();
        $headerNames = array_map(fn ($h) => $h->getName(), $headers);
        $this->assertContains('Host', $headerNames);
        $this->assertContains('Accept', $headerNames);

        // Verify body
        $this->assertEquals('name=value', $har_request->getPostData()->getText());
    }

    public function testIsResponseCached(): void
    {
        $request = (new Request())
          ->setBodySize(0);
        $this->assertTrue($request->isResponseCached());

        $request->setBodySize(100);
        $this->assertFalse($request->isResponseCached());
    }

    public function testHasPostData(): void
    {
        $request = new Request();
        $this->assertFalse($request->hasPostData());

        $request->setPostData(new PostData());
        $this->assertTrue($request->hasPostData());
    }

    public function testGetQueryString(): void
    {
        $request = new Request();
        $this->assertEquals([], $request->getQueryString());

        $queryParams = [
            (new \Deviantintegral\Har\Params())->setName('foo')->setValue('bar'),
            (new \Deviantintegral\Har\Params())->setName('baz')->setValue('qux'),
        ];
        $request->setQueryString($queryParams);
        $this->assertEquals($queryParams, $request->getQueryString());
    }

    public function testSetHeadersCalculatesCorrectSize(): void
    {
        $request = new Request();

        // Test with single header: "Host: www.example.com"
        // Size calculation: strlen("Host") + 2 + strlen("www.example.com") + 2 = 4 + 2 + 15 + 2 = 23
        // Plus final 2 for double CRLF: 23 + 2 = 25
        $headers = [(new Header())->setName('Host')->setValue('www.example.com')];
        $request->setHeaders($headers);
        $this->assertSame(25, $request->getHeadersSize());
    }

    public function testSetHeadersWithMultipleHeaders(): void
    {
        $request = new Request();

        // Test with multiple headers to ensure correct calculation
        // "Host: example.com" = 4 + 2 + 11 + 2 = 19
        // "Accept: */*" = 6 + 2 + 3 + 2 = 13
        // Total = 19 + 13 + 2 (final CRLF) = 34
        $headers = [
            (new Header())->setName('Host')->setValue('example.com'),
            (new Header())->setName('Accept')->setValue('*/*'),
        ];
        $request->setHeaders($headers);
        $this->assertSame(34, $request->getHeadersSize());
    }

    public function testSetHeadersWithEmptyArray(): void
    {
        $request = new Request();

        // Test with no headers
        // Size should be just the final 2 bytes for double CRLF
        $request->setHeaders([]);
        $this->assertSame(2, $request->getHeadersSize());
    }

    public function testSetHeadersWithSingleCharacterValues(): void
    {
        $request = new Request();

        // Test with minimal header to ensure each +2 is necessary
        // "A: B" = 1 + 2 + 1 + 2 = 6, plus final 2 = 8
        $headers = [(new Header())->setName('A')->setValue('B')];
        $request->setHeaders($headers);
        $this->assertSame(8, $request->getHeadersSize());
    }

    public function testFromPsr7ServerRequestWithEmptyCookies(): void
    {
        $uri = new Uri('https://www.example.com/path');
        $psr7 = new \GuzzleHttp\Psr7\ServerRequest('GET', $uri);
        $psr7 = $psr7->withCookieParams([]); // Empty cookies

        $har_request = Request::fromPsr7ServerRequest($psr7);

        // Verify no cookies are set on the HAR request
        $this->assertEquals([], $har_request->getCookies());
    }

    public function testFromPsr7ServerRequestWithCookies(): void
    {
        $uri = new Uri('https://www.example.com/path');
        $psr7 = new \GuzzleHttp\Psr7\ServerRequest('GET', $uri);
        $psr7 = $psr7->withCookieParams(['session' => 'abc123', 'user' => 'john']);

        $har_request = Request::fromPsr7ServerRequest($psr7);

        // Verify cookies are set correctly
        $cookies = $har_request->getCookies();
        $this->assertCount(2, $cookies);
        $this->assertEquals('session', $cookies[0]->getName());
        $this->assertEquals('abc123', $cookies[0]->getValue());
        $this->assertEquals('user', $cookies[1]->getName());
        $this->assertEquals('john', $cookies[1]->getValue());
    }

    public function testFromPsr7ServerRequestWithEmptyQueryParams(): void
    {
        $uri = new Uri('https://www.example.com/path');
        $psr7 = new \GuzzleHttp\Psr7\ServerRequest('GET', $uri);
        $psr7 = $psr7->withQueryParams([]); // Empty query params

        $har_request = Request::fromPsr7ServerRequest($psr7);

        // Verify no query params are set on the HAR request
        $this->assertEquals([], $har_request->getQueryString());
    }

    public function testFromPsr7ServerRequestWithQueryParams(): void
    {
        $uri = new Uri('https://www.example.com/path');
        $psr7 = new \GuzzleHttp\Psr7\ServerRequest('GET', $uri);
        $psr7 = $psr7->withQueryParams(['foo' => 'bar', 'baz' => 'qux']);

        $har_request = Request::fromPsr7ServerRequest($psr7);

        // Verify query params are set correctly
        $queryParams = $har_request->getQueryString();
        $this->assertCount(2, $queryParams);
        $this->assertEquals('foo', $queryParams[0]->getName());
        $this->assertEquals('bar', $queryParams[0]->getValue());
        $this->assertEquals('baz', $queryParams[1]->getName());
        $this->assertEquals('qux', $queryParams[1]->getValue());
    }
}
