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

    public function testGetHeaders(): void
    {
        $headers = $this->response->getHeaders();
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

    public function testWithStatusDoesNotModifyOriginal(): void
    {
        $original = $this->response;
        $originalStatus = $original->getStatusCode();
        $originalReasonPhrase = $original->getReasonPhrase();

        $modified = $original->withStatus(404, 'Not Found');

        // Original should have the same status
        $this->assertEquals($originalStatus, $original->getStatusCode());
        $this->assertEquals($originalReasonPhrase, $original->getReasonPhrase());

        // Modified should have the new status
        $this->assertEquals(404, $modified->getStatusCode());
        $this->assertEquals('Not Found', $modified->getReasonPhrase());
    }

    public function testWithBodyDoesNotModifyOriginal(): void
    {
        $original = $this->response;

        // Capture the state before calling withBody
        $originalHarBefore = $original->getHarResponse();
        $originalContentSizeBefore = $originalHarBefore->getContent()->getSize();

        $newBody = \GuzzleHttp\Psr7\Utils::streamFor('New content');
        $modified = $original->withBody($newBody);

        // Modified should have the new body
        $this->assertEquals('New content', (string) $modified->getBody());

        // Get the HAR response after calling withBody to verify it wasn't modified
        $originalHarAfter = $original->getHarResponse();

        // The content size should be the same as before
        $this->assertEquals(
            $originalContentSizeBefore,
            $originalHarAfter->getContent()->getSize()
        );
    }

    public function testGetHarResponseReturnsClone(): void
    {
        $harResponse1 = $this->response->getHarResponse();
        $harResponse2 = $this->response->getHarResponse();

        // Modifying one should not affect the other
        $harResponse1->setStatus(404);

        $this->assertEquals(404, $harResponse1->getStatus());
        $this->assertNotEquals(404, $harResponse2->getStatus());
    }
}
