<?php

declare(strict_types=1);

namespace Deviantintegral\Har\Tests\Unit;

use Deviantintegral\Har\Content;
use Deviantintegral\Har\Cookie;
use Deviantintegral\Har\Header;
use Deviantintegral\Har\Request;

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
          ->setContent(new Content())
          ->setCookies([(new Cookie())->setPath('/')])
          ->setHeaders([new Header()])
          ->setHeadersSize(0)
          ->setHttpVersion('2.0')
          ->setComment('Test comment');

        $serialized = $serializer->serialize($request, 'json');
        $this->assertEquals(
          [
            'httpVersion' => $request->getHttpVersion(),
            'cookies' => json_decode($serializer->serialize($request->getCookies(), 'json'), true),
            'headers' => json_decode($serializer->serialize($request->getHeaders(), 'json'), true),
            'content' => json_decode($serializer->serialize($request->getContent(), 'json'), true),
            'headersSize' => $request->getHeadersSize(),
            'bodySize' => $request->getBodySize(),
            'comment' => $request->getComment(),
          ],
          json_decode($serialized, true)
        );

        $this->assertDeserialize($serialized, Request::class, $request);
    }
}
