<?php

declare(strict_types=1);

namespace Deviantintegral\Har\Tests\Unit;

use Deviantintegral\Har\Content;
use Deviantintegral\Har\Header;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Uri;

/**
 * @covers \Deviantintegral\Har\Response
 */
class ResponseTest extends HarTestBase
{
    public function testFromPsr7()
    {
        $psr7 = new Response(200, ['Content-Type' => 'text/plain'], 'testing', '2.0', 'Who needs reasons?');
        $response = \Deviantintegral\Har\Response::fromPsr7Response($psr7);
        $this->assertEquals(200, $response->getStatus());
        $this->assertEquals([
            (new Header())->setName('Content-Type')->setValue('text/plain'),
        ], $response->getHeaders());
        $this->assertEquals('testing', $response->getContent()->getText());
        $this->assertEquals('HTTP/2.0', $response->getHttpVersion());
        $this->assertEquals('Who needs reasons?', $response->getStatusText());
    }

    public function testGetSetStatus()
    {
        $response = (new \Deviantintegral\Har\Response())->setStatus(404);
        $this->assertEquals(404, $response->getStatus());
    }

    public function testGetSetStatusText()
    {
        $response = (new \Deviantintegral\Har\Response())->setStatusText('Not Found');
        $this->assertEquals('Not Found', $response->getStatusText());
    }

    public function testGetSetContent()
    {
        $content = (new Content())->setText('test content');
        $response = (new \Deviantintegral\Har\Response())->setContent($content);
        $this->assertSame($content, $response->getContent());
    }

    public function testGetSetRedirectURL()
    {
        $uri = new Uri('https://www.example.com/redirect');
        $response = (new \Deviantintegral\Har\Response())->setRedirectURL($uri);
        $this->assertSame($uri, $response->getRedirectURL());
    }

    public function testSerialize()
    {
        $serializer = $this->getSerializer();
        $content = (new Content())->setText('test');
        $uri = new Uri('https://www.example.com/redirect');

        $response = (new \Deviantintegral\Har\Response())
          ->setStatus(301)
          ->setStatusText('Moved Permanently')
          ->setContent($content)
          ->setRedirectURL($uri)
          ->setBodySize(4)
          ->setHttpVersion('HTTP/1.1')
          ->setComment('Test redirect');

        $serialized = $serializer->serialize($response, 'json');
        $decoded = json_decode($serialized, true);

        $this->assertEquals(301, $decoded['status']);
        $this->assertEquals('Moved Permanently', $decoded['statusText']);
        $this->assertEquals('https://www.example.com/redirect', $decoded['redirectURL']);
        $this->assertEquals('Test redirect', $decoded['comment']);

        $deserialized = $serializer->deserialize(
            $serialized,
            \Deviantintegral\Har\Response::class,
            'json'
        );
        $this->assertEquals($response->getStatus(), $deserialized->getStatus());
        $this->assertEquals($response->getStatusText(), $deserialized->getStatusText());
        $this->assertEquals((string) $response->getRedirectURL(), (string) $deserialized->getRedirectURL());
        $this->assertEquals($response->getComment(), $deserialized->getComment());
    }
}
