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
    public function testFromPsr7(): void
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

    public function testGetSetStatusText(): void
    {
        $response = (new \Deviantintegral\Har\Response())->setStatusText('Not Found');
        $this->assertEquals('Not Found', $response->getStatusText());
    }

    public function testGetSetContent(): void
    {
        $content = (new Content())->setText('test content');
        $response = (new \Deviantintegral\Har\Response())->setContent($content);
        $this->assertSame($content, $response->getContent());
    }

    public function testGetSetRedirectURL(): void
    {
        $uri = new Uri('https://www.example.com/redirect');
        $response = (new \Deviantintegral\Har\Response())->setRedirectURL($uri);
        $this->assertSame($uri, $response->getRedirectURL());
    }

    public function testSerialize(): void
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

    public function testSetHeadersCalculatesCorrectSize(): void
    {
        $response = new \Deviantintegral\Har\Response();

        // Test with single header: "Content-Type: application/json"
        // Size calculation: strlen("Content-Type") + 2 + strlen("application/json") + 2 = 12 + 2 + 16 + 2 = 32
        // Plus final 2 for double CRLF: 32 + 2 = 34
        $headers = [(new Header())->setName('Content-Type')->setValue('application/json')];
        $response->setHeaders($headers);
        $this->assertSame(34, $response->getHeadersSize());
    }

    public function testSetHeadersWithMultipleHeaders(): void
    {
        $response = new \Deviantintegral\Har\Response();

        // Test with multiple headers to ensure correct calculation
        // "Content-Type: text/html" = 12 + 2 + 9 + 2 = 25
        // "Server: nginx" = 6 + 2 + 5 + 2 = 15
        // Total = 25 + 15 + 2 (final CRLF) = 42
        $headers = [
            (new Header())->setName('Content-Type')->setValue('text/html'),
            (new Header())->setName('Server')->setValue('nginx'),
        ];
        $response->setHeaders($headers);
        $this->assertSame(42, $response->getHeadersSize());
    }

    public function testSetHeadersWithEmptyArray(): void
    {
        $response = new \Deviantintegral\Har\Response();

        // Test with no headers
        // Size should be just the final 2 bytes for double CRLF
        $response->setHeaders([]);
        $this->assertSame(2, $response->getHeadersSize());
    }

    public function testSetHeadersWithSingleCharacterValues(): void
    {
        $response = new \Deviantintegral\Har\Response();

        // Test with minimal header to ensure each +2 is necessary
        // "X: Y" = 1 + 2 + 1 + 2 = 6, plus final 2 = 8
        $headers = [(new Header())->setName('X')->setValue('Y')];
        $response->setHeaders($headers);
        $this->assertSame(8, $response->getHeadersSize());
    }
}
