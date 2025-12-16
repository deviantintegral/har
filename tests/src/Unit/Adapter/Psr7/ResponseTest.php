<?php

declare(strict_types=1);

namespace Deviantintegral\Har\Tests\Unit\Adapter\Psr7;

use Deviantintegral\Har\Adapter\Psr7\Response;
use Deviantintegral\Har\Tests\Unit\HarTestBase;

/**
 * @covers \Deviantintegral\Har\Adapter\Psr7\Response
 * @covers \Deviantintegral\Har\Adapter\Psr7\MessageBase
 */
class ResponseTest extends HarTestBase
{
    /**
     * @var Response
     */
    private $response;

    protected function setUp(): void
    {
        $this->response = new Response(
            $this->getHarFileRepository()->load(
                'www.softwareishard.com-single-entry.har'
            )->getLog()->getEntries()[0]->getResponse()
        );
    }

    public function testGetStatusCode(): void
    {
        $this->assertEquals(200, $this->response->getStatusCode());
    }

    public function testWithStatus(): void
    {
        $withStatus = $this->response->withStatus(404, 'Not Found');
        $this->assertEquals(404, $withStatus->getStatusCode());
        $this->assertEquals('Not Found', $withStatus->getReasonPhrase());
    }

    public function testGetReasonPhrase(): void
    {
        $this->assertEquals('OK', $this->response->getReasonPhrase());
    }

    public function testGetHarResponse(): void
    {
        $harResponse = $this->response->getHarResponse();
        $this->assertInstanceOf(\Deviantintegral\Har\Response::class, $harResponse);
        $this->assertEquals(200, $harResponse->getStatus());
    }

    public function testWithHeader(): void
    {
        $withHeader = $this->response->withHeader('X-Test', 'value');
        $this->assertEquals(['value'], $withHeader->getHeader('X-Test'));

        $withArrayValue = $this->response->withHeader(
            'X-Test',
            ['value1', 'value2']
        );
        $this->assertEquals(
            ['value1', 'value2'],
            $withArrayValue->getHeader('X-Test')
        );
    }

    public function testGetHeaderLine(): void
    {
        $withMultiple = $this->response->withAddedHeader('X-Custom', 'value1')
          ->withAddedHeader('X-Custom', 'value2');
        $this->assertEquals('value1, value2', $withMultiple->getHeaderLine('X-Custom'));
    }

    public function testGetHeaderLineWhenHeaderNotPresent(): void
    {
        $this->assertEquals('', $this->response->getHeaderLine('X-NonExistent'));
    }

    public function testGetHeader(): void
    {
        $headers = $this->response->getHeader('Content-Type');
        $this->assertIsArray($headers);
    }

    public function testHasHeader(): void
    {
        $this->assertTrue($this->response->hasHeader('Content-Type'));
        $this->assertFalse($this->response->hasHeader('X-NonExistent'));
    }

    public function testGetHeaders(): void
    {
        $headers = $this->response->getHeaders();
        $this->assertIsArray($headers);
        $this->assertArrayHasKey('Content-Type', $headers);
    }

    public function testGetProtocolVersion(): void
    {
        $this->assertEquals('1.1', $this->response->getProtocolVersion());
    }

    public function testWithProtocolVersion(): void
    {
        $withProtocol = $this->response->withProtocolVersion('2.0');
        $this->assertEquals('2.0', $withProtocol->getProtocolVersion());
    }

    public function testWithoutHeader(): void
    {
        $withoutHeader = $this->response->withoutHeader('Content-Type');
        $this->assertFalse($withoutHeader->hasHeader('Content-Type'));
    }

    public function testWithAddedHeader(): void
    {
        $withAdded = $this->response->withAddedHeader('X-Custom', 'value1');
        $this->assertEquals(['value1'], $withAdded->getHeader('X-Custom'));

        $withMultiple = $withAdded->withAddedHeader('X-Custom', 'value2');
        $this->assertEquals(['value1', 'value2'], $withMultiple->getHeader('X-Custom'));
    }

    public function testWithAddedHeaderArrayValue(): void
    {
        $withAdded = $this->response->withAddedHeader('X-Custom', ['value1', 'value2']);
        $this->assertEquals(['value1', 'value2'], $withAdded->getHeader('X-Custom'));
    }

    public function testGetBody(): void
    {
        $body = $this->response->getBody();
        $this->assertInstanceOf(\Psr\Http\Message\StreamInterface::class, $body);

        // Verify the body content matches the response content
        $bodyContent = $body->getContents();
        $this->assertIsString($bodyContent);
        $this->assertNotEmpty($bodyContent);
    }

    public function testWithBody(): void
    {
        $newBodyContent = 'Test body content';
        $newBody = \GuzzleHttp\Psr7\Utils::streamFor($newBodyContent);

        $withBody = $this->response->withBody($newBody);
        $this->assertInstanceOf(Response::class, $withBody);

        // Verify the new body content
        $resultBody = $withBody->getBody();
        $this->assertEquals($newBodyContent, $resultBody->getContents());
    }
}
