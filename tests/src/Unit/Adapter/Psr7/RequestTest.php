<?php

declare(strict_types=1);

namespace Deviantintegral\Har\Tests\Unit\Adapter\Psr7;

use Deviantintegral\Har\Adapter\Psr7\Request;
use Deviantintegral\Har\Tests\Unit\HarTestBase;
use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\Psr7\Utils;
use Psr\Http\Message\StreamInterface;

class RequestTest extends HarTestBase
{
    /**
     * @var Request
     */
    private $getRequest;

    /**
     * @var Request
     */
    private $postRequest;

    protected function setUp(): void
    {
        $this->getRequest = new Request(
            $this->getHarFileRepository()->load(
                'www.softwareishard.com-single-entry.har'
            )->getLog()->getEntries()[0]->getRequest()
        );

        $this->postRequest = new Request(
            $this->getHarFileRepository()->load(
                'www.softwareishard.com-empty-login.har'
            )->getLog()->getEntries()[0]->getRequest()
        );
    }

    public function testWithRequestTarget(): void
    {
        $absolute_form = $this->getRequest->withRequestTarget(
            'https://www.example.com/home'
        );
        $this->assertEquals(
            'https://www.example.com/home',
            $absolute_form->getRequestTarget()
        );
    }

    public function testGetBody(): void
    {
        $stream = $this->postRequest->getBody();
        $this->assertInstanceOf(StreamInterface::class, $stream);
        $this->assertEquals('log=&pwd=&wp-submit=Log+In&redirect_to=http%3A%2F%2Fwww.softwareishard.com%2Fblog%2Fwp-admin%2F&testcookie=1', (string) $stream);
    }

    public function testWithHeader(): void
    {
        $with_header = $this->getRequest->withHeader('X-Test', 'server');
        $this->assertEquals(['server'], $with_header->getHeader('X-Test'));

        $with_array_value = $this->getRequest->withHeader(
            'X-Test',
            ['server1', 'server2']
        );
        $this->assertEquals(
            ['server1', 'server2'],
            $with_array_value->getHeader('X-Test')
        );
    }

    public function testGetHeaderLine(): void
    {
        $with_multiple = $this->getRequest->withAddedHeader('Accept', '*/*');
        $this->assertEquals('text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8, */*', $with_multiple->getHeaderLine('Accept'));
    }

    public function testGetHeaderLineCaseInsensitive(): void
    {
        // Test that getHeaderLine works with different case variations
        $expected = 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8';
        $this->assertEquals($expected, $this->getRequest->getHeaderLine('Accept'));
        $this->assertEquals($expected, $this->getRequest->getHeaderLine('accept'));
        $this->assertEquals($expected, $this->getRequest->getHeaderLine('ACCEPT'));
        $this->assertEquals($expected, $this->getRequest->getHeaderLine('AcCePt'));
    }

    public function testGetHeader(): void
    {
        $this->assertSame(
            ['1'],
            $this->getRequest->getHeader('Upgrade-Insecure-Requests')
        );
    }

    public function testGetHeaderCaseInsensitive(): void
    {
        // Test that getHeader works with different case variations
        $expected = ['text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8'];
        $this->assertEquals($expected, $this->getRequest->getHeader('Accept'));
        $this->assertEquals($expected, $this->getRequest->getHeader('accept'));
        $this->assertEquals($expected, $this->getRequest->getHeader('ACCEPT'));
        $this->assertEquals($expected, $this->getRequest->getHeader('AcCePt'));
    }

    public function testWithBody(): void
    {
        $with_body = $this->postRequest->withBody(Utils::streamFor('a=1&b=2'));
        $this->assertEquals('a=1&b=2', $with_body->getBody()->getContents());
    }

    public function testGetRequestTarget(): void
    {
        $this->assertEquals(
            'http://www.softwareishard.com/blog/har-12-spec/',
            $this->getRequest->getRequestTarget()
        );
    }

    public function testWithUri(): void
    {
        $uri = new Uri('http://www.example.com/');
        $with_uri = $this->getRequest->withUri($uri);
        $this->assertSame($uri, $with_uri->getUri());
    }

    public function testHasHeader(): void
    {
        $this->assertTrue($this->getRequest->hasHeader('Accept'));
        $this->assertFalse($this->getRequest->hasHeader('Kittens'));
    }

    public function testHasHeaderCaseInsensitive(): void
    {
        // Test that hasHeader works with different case variations
        $this->assertTrue($this->getRequest->hasHeader('Accept'));
        $this->assertTrue($this->getRequest->hasHeader('accept'));
        $this->assertTrue($this->getRequest->hasHeader('ACCEPT'));
        $this->assertTrue($this->getRequest->hasHeader('AcCePt'));
    }

    public function testGetHeaders(): void
    {
        $headers = $this->getRequest->getHeaders();
        $this->assertEquals(
            [
                'Accept' => [
                    0 => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                ],
                'Upgrade-Insecure-Requests' => [
                    0 => '1',
                ],
                'Host' => [
                    0 => 'www.softwareishard.com',
                ],
                'User-Agent' => [
                    0 => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_14_6) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/12.1.2 Safari/605.1.15',
                ],
                'Accept-Language' => [
                    0 => 'en-ca',
                ],
                'Accept-Encoding' => [
                    0 => 'gzip, deflate',
                ],
                'Connection' => [
                    0 => 'keep-alive',
                ],
            ],
            $headers
        );
    }

    public function testGetProtocolVersion(): void
    {
        $this->assertEquals('1.1', $this->getRequest->getProtocolVersion());
    }

    public function testWithProtocolVersion(): void
    {
        $with_protocol_version = $this->getRequest->withProtocolVersion('2.0');
        $this->assertEquals('2.0', $with_protocol_version->getProtocolVersion());
    }

    public function testWithoutHeader(): void
    {
        $without_header = $this->getRequest->withoutHeader('Accept');
        $this->assertFalse($without_header->hasHeader('Accept'));
    }

    public function testWithoutHeaderCaseInsensitive(): void
    {
        // Test that withoutHeader works with different case variations
        $without_accept_lower = $this->getRequest->withoutHeader('accept');
        $this->assertFalse($without_accept_lower->hasHeader('Accept'));
        $this->assertFalse($without_accept_lower->hasHeader('accept'));

        $without_accept_upper = $this->getRequest->withoutHeader('ACCEPT');
        $this->assertFalse($without_accept_upper->hasHeader('Accept'));
        $this->assertFalse($without_accept_upper->hasHeader('ACCEPT'));

        $without_accept_mixed = $this->getRequest->withoutHeader('AcCePt');
        $this->assertFalse($without_accept_mixed->hasHeader('Accept'));
        $this->assertFalse($without_accept_mixed->hasHeader('AcCePt'));
    }

    public function testWithAddedHeader(): void
    {
        $with_added = $this->getRequest->withAddedHeader('Accept', '*/*');
        $this->assertEquals([
            'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            '*/*',
        ], $with_added->getHeader('Accept'));
    }

    public function testGetMethod(): void
    {
        $this->assertEquals('GET', $this->getRequest->getMethod());
    }

    public function testWithMethod(): void
    {
        $this->assertEquals('OPTIONS', $this->getRequest->withMethod('OPTIONS')->getMethod());
    }

    public function testGetUri(): void
    {
        $this->assertEquals(new Uri('http://www.softwareishard.com/blog/har-12-spec/'), $this->getRequest->getUri());
    }
}
