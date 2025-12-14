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
    public function testSerialize()
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

    public function testFromPsr7()
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

    public function testFromPsr7ServerRequest()
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

    public function testIsResponseCached()
    {
        $request = (new Request())
          ->setBodySize(0);
        $this->assertTrue($request->isResponseCached());

        $request->setBodySize(100);
        $this->assertFalse($request->isResponseCached());
    }

    public function testHasPostData()
    {
        $request = new Request();
        $this->assertFalse($request->hasPostData());

        $request->setPostData(new PostData());
        $this->assertTrue($request->hasPostData());
    }

    public function testGetSetUrl()
    {
        $uri = new Uri('https://www.example.com/path');
        $request = (new Request())->setUrl($uri);
        $this->assertSame($uri, $request->getUrl());
    }

    public function testGetQueryString()
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

    public function testGetSetMethod()
    {
        $request = (new Request())->setMethod('POST');
        $this->assertEquals('POST', $request->getMethod());
    }
}
