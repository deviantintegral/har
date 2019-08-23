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
}
