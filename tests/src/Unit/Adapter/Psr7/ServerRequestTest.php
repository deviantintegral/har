<?php

declare(strict_types=1);

namespace Deviantintegral\Har\Tests\Unit\Adapter\Psr7;

use Deviantintegral\Har\Adapter\Psr7\ServerRequest;
use Deviantintegral\Har\Cookie;
use Deviantintegral\Har\Header;
use Deviantintegral\Har\Params;
use Deviantintegral\Har\PostData;
use Deviantintegral\Har\Request;
use Deviantintegral\Har\Tests\Unit\HarTestBase;
use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\Psr7\Utils;

class ServerRequestTest extends HarTestBase
{
    /**
     * @var Request
     */
    private $harRequest;

    /**
     * @var ServerRequest
     */
    private $serverRequest;

    protected function setUp(): void
    {
        // Create a HAR request with query params, cookies, and post data
        $this->harRequest = (new Request())
            ->setMethod('POST')
            ->setUrl(new Uri('https://www.example.com/path?foo=bar'))
            ->setHttpVersion('HTTP/1.1')
            ->setHeaders([
                (new Header())->setName('Host')->setValue('www.example.com'),
                (new Header())->setName('Content-Type')->setValue('application/x-www-form-urlencoded'),
            ])
            ->setQueryString([
                (new Params())->setName('foo')->setValue('bar'),
            ])
            ->setCookies([
                (new Cookie())->setName('session')->setValue('abc123'),
            ])
            ->setPostData(
                (new PostData())->setParams([
                    (new Params())->setName('username')->setValue('john'),
                    (new Params())->setName('password')->setValue('secret'),
                ])
            );

        $this->serverRequest = (new ServerRequest($this->harRequest))
            ->withCookieParams(['session' => 'abc123'])
            ->withQueryParams(['foo' => 'bar'])
            ->withParsedBody(['username' => 'john', 'password' => 'secret']);
    }

    public function testGetServerParams()
    {
        // Server params are not part of HAR spec, always returns empty array
        $this->assertEquals([], $this->serverRequest->getServerParams());
    }

    public function testGetCookieParams()
    {
        $this->assertEquals(
            ['session' => 'abc123'],
            $this->serverRequest->getCookieParams()
        );
    }

    public function testWithCookieParams()
    {
        $new = $this->serverRequest->withCookieParams(['new_cookie' => 'xyz789']);
        $this->assertEquals(['new_cookie' => 'xyz789'], $new->getCookieParams());
        // Verify immutability
        $this->assertEquals(['session' => 'abc123'], $this->serverRequest->getCookieParams());
    }

    public function testGetQueryParams()
    {
        $this->assertEquals(
            ['foo' => 'bar'],
            $this->serverRequest->getQueryParams()
        );
    }

    public function testWithQueryParams()
    {
        $new = $this->serverRequest->withQueryParams(['baz' => 'qux']);
        $this->assertEquals(['baz' => 'qux'], $new->getQueryParams());
        // Verify immutability
        $this->assertEquals(['foo' => 'bar'], $this->serverRequest->getQueryParams());
    }

    public function testGetUploadedFiles()
    {
        // Uploaded files are not part of HAR spec, always returns empty array
        $this->assertEquals([], $this->serverRequest->getUploadedFiles());
    }

    public function testWithUploadedFiles()
    {
        // Uploaded files are not part of HAR spec, this is a no-op
        $files = ['file' => 'mock_uploaded_file'];
        $this->expectException(\LogicException::class);
        $new = $this->serverRequest->withUploadedFiles($files);
    }

    public function testGetParsedBody()
    {
        $this->assertEquals(
            ['username' => 'john', 'password' => 'secret'],
            $this->serverRequest->getParsedBody()
        );
    }

    public function testWithParsedBody()
    {
        $new = $this->serverRequest->withParsedBody(['key' => 'value']);
        $this->assertEquals(['key' => 'value'], $new->getParsedBody());
        // Verify immutability
        $this->assertEquals(
            ['username' => 'john', 'password' => 'secret'],
            $this->serverRequest->getParsedBody()
        );
    }

    public function testWithParsedBodyNull()
    {
        $new = $this->serverRequest->withParsedBody(null);
        $this->assertNull($new->getParsedBody());
    }

    public function testWithParsedBodyInvalidType()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->serverRequest->withParsedBody('invalid');
    }

    public function testGetAttributes()
    {
        // Attributes are not part of HAR spec, always returns empty array
        $this->assertEquals([], $this->serverRequest->getAttributes());
    }

    public function testGetAttribute()
    {
        // Attributes are not part of HAR spec, always returns default
        $this->assertNull($this->serverRequest->getAttribute('custom_attr'));
        $this->assertNull($this->serverRequest->getAttribute('nonexistent'));
        $this->assertEquals('default', $this->serverRequest->getAttribute('nonexistent', 'default'));
    }

    public function testWithAttribute()
    {
        $this->expectException(\LogicException::class);
        $this->serverRequest->withAttribute('new_attr', 'new_value');
    }

    public function testWithoutAttribute()
    {
        // Attributes are not part of HAR spec, this is a no-op
        $new = $this->serverRequest->withoutAttribute('custom_attr');
        $this->assertNull($new->getAttribute('custom_attr'));
        $this->assertNull($this->serverRequest->getAttribute('custom_attr'));
    }

    public function testInheritedMethodsPreserveServerRequestState()
    {
        // Test that methods inherited from Request preserve ServerRequest-specific properties
        $new = $this->serverRequest->withMethod('GET');
        $this->assertEquals('GET', $new->getMethod());
        $this->assertEquals(['session' => 'abc123'], $new->getCookieParams());
        $this->assertEquals(['foo' => 'bar'], $new->getQueryParams());
    }

    public function testWithBody()
    {
        $new = $this->serverRequest->withBody(Utils::streamFor('new body'));
        $this->assertEquals('new body', $new->getBody()->getContents());
        // Verify ServerRequest state is preserved
        $this->assertEquals(['session' => 'abc123'], $new->getCookieParams());
        $this->assertEquals(['foo' => 'bar'], $new->getQueryParams());
    }

    public function testWithUri()
    {
        $newUri = new Uri('https://www.newexample.com/newpath');
        $new = $this->serverRequest->withUri($newUri);
        $this->assertEquals($newUri, $new->getUri());
        // Verify ServerRequest state is preserved
        $this->assertEquals(['session' => 'abc123'], $new->getCookieParams());
        $this->assertEquals(['foo' => 'bar'], $new->getQueryParams());
    }

    public function testWithHeader()
    {
        $new = $this->serverRequest->withHeader('X-Custom', 'value');
        $this->assertEquals(['value'], $new->getHeader('X-Custom'));
        // Verify ServerRequest state is preserved
        $this->assertEquals(['session' => 'abc123'], $new->getCookieParams());
        $this->assertEquals(['foo' => 'bar'], $new->getQueryParams());
    }

    public function testInitializeFromHarRequest()
    {
        // Test that a ServerRequest can be created from a HAR request
        // and properly extract query params, cookies, and parsed body
        $serverRequest = new ServerRequest($this->harRequest);

        // Should extract query params from HAR request
        $this->assertEquals(['foo' => 'bar'], $serverRequest->getQueryParams());

        // Should extract cookies from HAR request
        $this->assertEquals(['session' => 'abc123'], $serverRequest->getCookieParams());

        // Should extract parsed body from HAR POST params
        $this->assertEquals(
            ['username' => 'john', 'password' => 'secret'],
            $serverRequest->getParsedBody()
        );

        // Server params should be empty by default
        $this->assertEquals([], $serverRequest->getServerParams());
    }
}
