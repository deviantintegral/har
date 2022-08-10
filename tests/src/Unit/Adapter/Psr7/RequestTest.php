<?php

declare(strict_types=1);

namespace Deviantintegral\Har\Tests\Unit\Adapter\Psr7;

use Deviantintegral\Har\Adapter\Psr7\Request;
use Deviantintegral\Har\Tests\Unit\HarTestBase;
use GuzzleHttp\Psr7\Utils;
use GuzzleHttp\Psr7\Uri;
use Psr\Http\Message\StreamInterface;

class RequestTest extends HarTestBase
{
    /**
     * @var Request
     */
    private $getRequest;

    /**
     * @var \Deviantintegral\Har\Adapter\Psr7\Request
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

    public function testWithRequestTarget()
    {
        $absolute_form = $this->getRequest->withRequestTarget(
          'https://www.example.com/home'
        );
        $this->assertEquals(
          'https://www.example.com/home',
          $absolute_form->getRequestTarget()
        );
    }

    public function testGetBody()
    {
        $stream = $this->postRequest->getBody();
        $this->assertInstanceOf(StreamInterface::class, $stream);
        $this->assertEquals('log=&pwd=&wp-submit=Log+In&redirect_to=http%3A%2F%2Fwww.softwareishard.com%2Fblog%2Fwp-admin%2F&testcookie=1', (string) $stream);
    }

    public function testWithHeader()
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

    public function testGetHeaderLine()
    {
        $with_multiple = $this->getRequest->withAddedHeader('Accept', '*/*');
        $this->assertEquals('text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8, */*', $with_multiple->getHeaderLine('Accept'));
    }

    public function testGetHeader()
    {
        $this->assertSame(
          ['1'],
          $this->getRequest->getHeader('Upgrade-Insecure-Requests')
        );
    }

    public function testWithBody()
    {
        $with_body = $this->postRequest->withBody(Utils::streamFor('a=1&b=2'));
        $this->assertEquals('a=1&b=2', $with_body->getBody()->getContents());
    }

    public function testGetRequestTarget()
    {
        $this->assertEquals(
          'http://www.softwareishard.com/blog/har-12-spec/',
          $this->getRequest->getRequestTarget()
        );
    }

    public function testWithUri()
    {
        $uri = new Uri('http://www.example.com/');
        $with_uri = $this->getRequest->withUri($uri);
        $this->assertSame($uri, $with_uri->getUri());
    }

    public function testHasHeader()
    {
        $this->assertTrue($this->getRequest->hasHeader('Accept'));
        $this->assertFalse($this->getRequest->hasHeader('Kittens'));
    }

    public function testGetHeaders()
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

    public function testGetProtocolVersion()
    {
        $this->assertEquals('1.1', $this->getRequest->getProtocolVersion());
    }

    public function testWithProtocolVersion()
    {
        $with_protocol_version = $this->getRequest->withProtocolVersion('2.0');
        $this->assertEquals('2.0', $with_protocol_version->getProtocolVersion());
    }

    public function testWithoutHeader()
    {
        $without_header = $this->getRequest->withoutHeader('Accept');
        $this->assertFalse($without_header->hasHeader('Accept'));
    }

    public function testWithAddedHeader()
    {
        $with_added = $this->getRequest->withAddedHeader('Accept', '*/*');
        $this->assertEquals([
          'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
          '*/*',
        ], $with_added->getHeader('Accept'));
    }

    public function testGetMethod()
    {
        $this->assertEquals('GET', $this->getRequest->getMethod());
    }

    public function testWithMethod()
    {
        $this->assertEquals('OPTIONS', $this->getRequest->withMethod('OPTIONS')->getMethod());
    }

    public function testGetUri()
    {
        $this->assertEquals(new Uri('http://www.softwareishard.com/blog/har-12-spec/'), $this->getRequest->getUri());
    }
}
