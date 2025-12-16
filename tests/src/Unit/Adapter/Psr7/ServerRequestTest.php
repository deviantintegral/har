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

    public function testGetServerParams(): void
    {
        // Server params are not part of HAR spec, always returns empty array
        $this->assertEquals([], $this->serverRequest->getServerParams());
    }

    public function testGetCookieParams(): void
    {
        $this->assertEquals(
            ['session' => 'abc123'],
            $this->serverRequest->getCookieParams()
        );
    }

    public function testWithCookieParams(): void
    {
        $new = $this->serverRequest->withCookieParams(['new_cookie' => 'xyz789']);
        $this->assertEquals(['new_cookie' => 'xyz789'], $new->getCookieParams());
        // Verify immutability
        $this->assertEquals(['session' => 'abc123'], $this->serverRequest->getCookieParams());
    }

    public function testGetQueryParams(): void
    {
        $this->assertEquals(
            ['foo' => 'bar'],
            $this->serverRequest->getQueryParams()
        );
    }

    public function testWithQueryParams(): void
    {
        $new = $this->serverRequest->withQueryParams(['baz' => 'qux']);
        $this->assertEquals(['baz' => 'qux'], $new->getQueryParams());
        // Verify immutability
        $this->assertEquals(['foo' => 'bar'], $this->serverRequest->getQueryParams());
    }

    public function testGetUploadedFiles(): void
    {
        // Uploaded files are not part of HAR spec, always returns empty array
        $this->assertEquals([], $this->serverRequest->getUploadedFiles());
    }

    public function testWithUploadedFiles(): void
    {
        // Uploaded files are not part of HAR spec, this is a no-op
        $files = ['file' => 'mock_uploaded_file'];
        $this->expectException(\LogicException::class);
        $new = $this->serverRequest->withUploadedFiles($files);
    }

    public function testGetParsedBody(): void
    {
        $this->assertEquals(
            ['username' => 'john', 'password' => 'secret'],
            $this->serverRequest->getParsedBody()
        );
    }

    public function testWithParsedBody(): void
    {
        $new = $this->serverRequest->withParsedBody(['key' => 'value']);
        $this->assertEquals(['key' => 'value'], $new->getParsedBody());
        // Verify immutability
        $this->assertEquals(
            ['username' => 'john', 'password' => 'secret'],
            $this->serverRequest->getParsedBody()
        );
    }

    public function testWithParsedBodyNull(): void
    {
        $new = $this->serverRequest->withParsedBody(null);
        $this->assertNull($new->getParsedBody());
    }

    public function testWithParsedBodyInvalidType(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->serverRequest->withParsedBody('invalid'); // @phpstan-ignore argument.type
    }

    public function testGetAttributes(): void
    {
        // Attributes are not part of HAR spec, always returns empty array
        $this->assertEquals([], $this->serverRequest->getAttributes());
    }

    public function testGetAttribute(): void
    {
        // Attributes are not part of HAR spec, always returns default
        $this->assertNull($this->serverRequest->getAttribute('custom_attr'));
        $this->assertNull($this->serverRequest->getAttribute('nonexistent'));
        $this->assertEquals('default', $this->serverRequest->getAttribute('nonexistent', 'default'));
    }

    public function testWithAttribute(): void
    {
        $this->expectException(\LogicException::class);
        $this->serverRequest->withAttribute('new_attr', 'new_value');
    }

    public function testWithoutAttribute(): void
    {
        // Attributes are not part of HAR spec, this is a no-op
        $new = $this->serverRequest->withoutAttribute('custom_attr');
        $this->assertNull($new->getAttribute('custom_attr'));
        $this->assertNull($this->serverRequest->getAttribute('custom_attr'));
    }

    public function testInheritedMethodsPreserveServerRequestState(): void
    {
        // Test that methods inherited from Request preserve ServerRequest-specific properties
        $new = $this->serverRequest->withMethod('GET');
        $this->assertEquals('GET', $new->getMethod());
        $this->assertEquals(['session' => 'abc123'], $new->getCookieParams());
        $this->assertEquals(['foo' => 'bar'], $new->getQueryParams());
    }

    public function testWithBody(): void
    {
        $new = $this->serverRequest->withBody(Utils::streamFor('new body'));
        $this->assertEquals('new body', $new->getBody()->getContents());
        // Verify ServerRequest state is preserved
        $this->assertEquals(['session' => 'abc123'], $new->getCookieParams());
        $this->assertEquals(['foo' => 'bar'], $new->getQueryParams());
    }

    public function testWithUri(): void
    {
        $newUri = new Uri('https://www.newexample.com/newpath');
        $new = $this->serverRequest->withUri($newUri);
        $this->assertEquals($newUri, $new->getUri());
        // Verify ServerRequest state is preserved
        $this->assertEquals(['session' => 'abc123'], $new->getCookieParams());
        $this->assertEquals(['foo' => 'bar'], $new->getQueryParams());
    }

    public function testWithHeader(): void
    {
        $new = $this->serverRequest->withHeader('X-Custom', 'value');
        $this->assertEquals(['value'], $new->getHeader('X-Custom'));
        // Verify ServerRequest state is preserved
        $this->assertEquals(['session' => 'abc123'], $new->getCookieParams());
        $this->assertEquals(['foo' => 'bar'], $new->getQueryParams());
    }

    public function testInitializeFromHarRequest(): void
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

    public function testGetParsedBodyWithNoPostData(): void
    {
        // Test that getParsedBody returns null when there's no post data
        $harRequest = (new Request())
            ->setMethod('GET')
            ->setUrl(new Uri('https://www.example.com/path'));

        $serverRequest = new ServerRequest($harRequest);
        $this->assertNull($serverRequest->getParsedBody());
    }

    public function testGetParsedBodyWithFormUrlEncodedText(): void
    {
        // Test parsing application/x-www-form-urlencoded text
        $harRequest = (new Request())
            ->setMethod('POST')
            ->setUrl(new Uri('https://www.example.com/path'))
            ->setPostData(
                (new PostData())
                    ->setMimeType('application/x-www-form-urlencoded')
                    ->setText('username=john&password=secret')
            );

        $serverRequest = new ServerRequest($harRequest);
        $this->assertEquals(
            ['username' => 'john', 'password' => 'secret'],
            $serverRequest->getParsedBody()
        );
    }

    public function testGetParsedBodyWithNonFormEncodedText(): void
    {
        // Test that getParsedBody returns null for non-form-encoded text
        $harRequest = (new Request())
            ->setMethod('POST')
            ->setUrl(new Uri('https://www.example.com/path'))
            ->setPostData(
                (new PostData())
                    ->setMimeType('application/json')
                    ->setText('{"username":"john","password":"secret"}')
            );

        $serverRequest = new ServerRequest($harRequest);
        $this->assertNull($serverRequest->getParsedBody());
    }

    public function testWithParsedBodyObject(): void
    {
        // Test that withParsedBody works with objects
        $data = new \stdClass();
        $data->username = 'john';
        $data->password = 'secret';

        $new = $this->serverRequest->withParsedBody($data);
        $parsedBody = $new->getParsedBody();

        $this->assertIsArray($parsedBody);
        $this->assertEquals('john', $parsedBody['username']);
        $this->assertEquals('secret', $parsedBody['password']);
    }

    public function testWithRequestTarget(): void
    {
        $new = $this->serverRequest->withRequestTarget('https://www.example.com/newpath');
        $this->assertEquals('https://www.example.com/newpath', $new->getRequestTarget());
        // Verify ServerRequest state is preserved
        $this->assertEquals(['session' => 'abc123'], $new->getCookieParams());
        $this->assertEquals(['foo' => 'bar'], $new->getQueryParams());
    }

    public function testWithAddedHeader(): void
    {
        $new = $this->serverRequest->withAddedHeader('X-Custom', 'value1');
        $new = $new->withAddedHeader('X-Custom', 'value2');
        $this->assertEquals(['value1', 'value2'], $new->getHeader('X-Custom'));
        // Verify ServerRequest state is preserved
        $this->assertEquals(['session' => 'abc123'], $new->getCookieParams());
        $this->assertEquals(['foo' => 'bar'], $new->getQueryParams());
    }

    public function testWithoutHeader(): void
    {
        $new = $this->serverRequest->withoutHeader('Host');
        $this->assertFalse($new->hasHeader('Host'));
        // Verify ServerRequest state is preserved
        $this->assertEquals(['session' => 'abc123'], $new->getCookieParams());
        $this->assertEquals(['foo' => 'bar'], $new->getQueryParams());
    }

    public function testWithProtocolVersion(): void
    {
        $new = $this->serverRequest->withProtocolVersion('2.0');
        $this->assertEquals('2.0', $new->getProtocolVersion());
        // Verify ServerRequest state is preserved
        $this->assertEquals(['session' => 'abc123'], $new->getCookieParams());
        $this->assertEquals(['foo' => 'bar'], $new->getQueryParams());
    }

    public function testWithCookieParamsClonesHarRequest(): void
    {
        $original = $this->serverRequest;

        // Capture state before modification
        $originalCookiesBefore = $original->getCookieParams();
        $originalHarBefore = $original->getHarRequest();
        $originalCookiesCountBefore = \count($originalHarBefore->getCookies());

        $modified = $original->withCookieParams(['new_cookie' => 'xyz789']);

        // Verify the original wasn't modified
        $this->assertEquals($originalCookiesBefore, $original->getCookieParams());

        $originalHarAfter = $original->getHarRequest();
        $this->assertCount($originalCookiesCountBefore, $originalHarAfter->getCookies());

        // Verify modified has the new cookies
        $this->assertEquals(['new_cookie' => 'xyz789'], $modified->getCookieParams());
    }

    public function testWithQueryParamsClonesHarRequest(): void
    {
        $original = $this->serverRequest;

        // Capture state before modification
        $originalQueryBefore = $original->getQueryParams();
        $originalHarBefore = $original->getHarRequest();
        $originalQueryCountBefore = \count($originalHarBefore->getQueryString());

        $modified = $original->withQueryParams(['new_param' => 'value']);

        // Verify the original wasn't modified
        $this->assertEquals($originalQueryBefore, $original->getQueryParams());

        $originalHarAfter = $original->getHarRequest();
        $this->assertCount($originalQueryCountBefore, $originalHarAfter->getQueryString());

        // Verify modified has the new query params
        $this->assertEquals(['new_param' => 'value'], $modified->getQueryParams());
    }

    public function testWithParsedBodyClonesHarRequest(): void
    {
        $original = $this->serverRequest;

        // Capture state before modification
        $originalParsedBodyBefore = $original->getParsedBody();
        $originalHarBefore = $original->getHarRequest();
        $originalHasPostDataBefore = $originalHarBefore->hasPostData();

        $modified = $original->withParsedBody(['new_key' => 'new_value']);

        // Verify the original wasn't modified
        $this->assertEquals($originalParsedBodyBefore, $original->getParsedBody());

        $originalHarAfter = $original->getHarRequest();
        $this->assertEquals($originalHasPostDataBefore, $originalHarAfter->hasPostData());

        // Verify modified has the new parsed body
        $this->assertEquals(['new_key' => 'new_value'], $modified->getParsedBody());
    }
}
