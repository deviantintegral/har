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

    public function testWithRequestTargetThrowsExceptionWhenMissingScheme(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('must be an absolute-form target');

        // Missing scheme - should throw exception
        $this->getRequest->withRequestTarget('//www.example.com/home');
    }

    public function testWithRequestTargetThrowsExceptionWhenMissingHost(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('must be an absolute-form target');

        // Missing host - should throw exception
        $this->getRequest->withRequestTarget('file:///home');
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

    public function testWithUriSetsHostHeaderByDefault(): void
    {
        $newUri = new Uri('http://newhost.example.com/path');

        // Call withUri without $preserveHost parameter (defaults to false)
        $modified = $this->getRequest->withUri($newUri);

        // The Host header should be updated to the new URI's host
        $this->assertEquals(['newhost.example.com'], $modified->getHeader('Host'));
    }

    public function testWithUriPreservesHostWhenRequested(): void
    {
        $originalHost = $this->getRequest->getHeader('Host');
        $newUri = new Uri('http://newhost.example.com/path');

        // Call withUri with $preserveHost = true
        $modified = $this->getRequest->withUri($newUri, true);

        // The Host header should remain unchanged
        $this->assertEquals($originalHost, $modified->getHeader('Host'));

        // Verify it's NOT set to the new URI's host
        $this->assertNotEquals(['newhost.example.com'], $modified->getHeader('Host'));
    }

    public function testWithUriWithEmptyHostDoesNotSetHeader(): void
    {
        $originalHeaders = $this->getRequest->getHeaders();

        // Create a URI without a host (file:// scheme)
        $uriWithoutHost = new Uri('file:///path/to/file');

        // Call withUri with preserveHost = false (default)
        $modified = $this->getRequest->withUri($uriWithoutHost, false);

        // The headers should remain the same since there's no host to set
        $this->assertEquals($originalHeaders, $modified->getHeaders());
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

    public function testWithHeaderDoesNotModifyOriginal(): void
    {
        $original = $this->getRequest;
        $originalHeaders = $original->getHeaders();

        $modified = $original->withHeader('X-Test', 'value');

        // Original should not have the new header
        $this->assertFalse($original->hasHeader('X-Test'));
        $this->assertEquals($originalHeaders, $original->getHeaders());

        // Modified should have the new header
        $this->assertTrue($modified->hasHeader('X-Test'));
    }

    public function testWithoutHeaderDoesNotModifyOriginal(): void
    {
        $original = $this->getRequest;
        $this->assertTrue($original->hasHeader('Accept'));

        $modified = $original->withoutHeader('Accept');

        // Original should still have the header
        $this->assertTrue($original->hasHeader('Accept'));

        // Modified should not have the header
        $this->assertFalse($modified->hasHeader('Accept'));
    }

    public function testWithAddedHeaderDoesNotModifyOriginal(): void
    {
        $original = $this->getRequest;
        $originalAccept = $original->getHeader('Accept');

        $modified = $original->withAddedHeader('Accept', '*/*');

        // Original should have the same header values
        $this->assertEquals($originalAccept, $original->getHeader('Accept'));

        // Modified should have the additional value
        $this->assertCount(\count($originalAccept) + 1, $modified->getHeader('Accept'));
    }

    public function testWithRequestTargetDoesNotModifyOriginal(): void
    {
        $original = $this->getRequest;
        $originalTarget = $original->getRequestTarget();

        $modified = $original->withRequestTarget('https://www.example.com/home');

        // Original should have the same request target
        $this->assertEquals($originalTarget, $original->getRequestTarget());

        // Modified should have the new request target
        $this->assertEquals('https://www.example.com/home', $modified->getRequestTarget());
    }

    public function testWithMethodDoesNotModifyOriginal(): void
    {
        $original = $this->getRequest;
        $originalMethod = $original->getMethod();

        $modified = $original->withMethod('POST');

        // Original should have the same method
        $this->assertEquals($originalMethod, $original->getMethod());

        // Modified should have the new method
        $this->assertEquals('POST', $modified->getMethod());
    }

    public function testWithUriDoesNotModifyOriginal(): void
    {
        $original = $this->getRequest;
        $originalUri = $original->getUri();

        $newUri = new Uri('http://www.example.com/');
        $modified = $original->withUri($newUri);

        // Original should have the same URI
        $this->assertEquals($originalUri, $original->getUri());

        // Modified should have the new URI
        $this->assertSame($newUri, $modified->getUri());
    }

    public function testWithBodyDoesNotModifyOriginal(): void
    {
        // Create a fresh request for this test
        $original = new Request(
            $this->getHarFileRepository()->load(
                'www.softwareishard.com-empty-login.har'
            )->getLog()->getEntries()[0]->getRequest()
        );

        // Capture the state before calling withBody
        $originalHarBefore = $original->getHarRequest();
        $originalHasPostDataBefore = $originalHarBefore->hasPostData();
        $originalPostDataBefore = $originalHarBefore->hasPostData() ?
            $originalHarBefore->getPostData() : null;

        $newBody = Utils::streamFor('a=1&b=2');
        $modified = $original->withBody($newBody);

        // Modified should have the new body
        $this->assertEquals('a=1&b=2', (string) $modified->getBody());

        // Get the HAR request after calling withBody to verify it wasn't modified
        $originalHarAfter = $original->getHarRequest();
        $this->assertEquals($originalHasPostDataBefore, $originalHarAfter->hasPostData());

        // Verify the PostData object itself wasn't modified if it existed
        if (null !== $originalPostDataBefore && $originalHarAfter->hasPostData()) {
            // They should be equal but not the same object (because getHarRequest clones)
            $this->assertEquals(
                $originalPostDataBefore->getBodySize(),
                $originalHarAfter->getPostData()->getBodySize()
            );
        }
    }

    public function testWithBodyPreservesExistingPostData(): void
    {
        // Start with a request that has post data with params
        $harRequest = $this->getHarFileRepository()->load(
            'www.softwareishard.com-empty-login.har'
        )->getLog()->getEntries()[0]->getRequest();

        $original = new Request($harRequest);

        // Verify it has post data
        $this->assertTrue($original->getHarRequest()->hasPostData());

        // Call withBody with new content
        $newBody = Utils::streamFor('new=content');
        $modified = $original->withBody($newBody);

        // Modified should have the new body text
        $this->assertEquals('new=content', (string) $modified->getBody());

        // Verify the modified request has post data set
        $modifiedHar = $modified->getHarRequest();
        $this->assertTrue($modifiedHar->hasPostData());
        $this->assertEquals('new=content', $modifiedHar->getPostData()->getText());
    }

    public function testWithBodyCreatesPostDataWhenMissing(): void
    {
        // Start with a GET request that has no post data
        $harRequest = $this->getHarFileRepository()->load(
            'www.softwareishard.com-single-entry.har'
        )->getLog()->getEntries()[0]->getRequest();

        $original = new Request($harRequest);

        // Verify it has no post data initially
        $this->assertFalse($original->getHarRequest()->hasPostData());

        // Call withBody with new content
        $newBody = Utils::streamFor('new=content');
        $modified = $original->withBody($newBody);

        // Modified should have the new body text
        $this->assertEquals('new=content', (string) $modified->getBody());

        // Verify the modified request has post data set
        $modifiedHar = $modified->getHarRequest();
        $this->assertTrue($modifiedHar->hasPostData());
        $this->assertEquals('new=content', $modifiedHar->getPostData()->getText());
    }

    public function testGetHarRequestReturnsClone(): void
    {
        $harRequest1 = $this->getRequest->getHarRequest();
        $harRequest2 = $this->getRequest->getHarRequest();

        // Modifying one should not affect the other
        $harRequest1->setMethod('POST');

        $this->assertEquals('POST', $harRequest1->getMethod());
        $this->assertNotEquals('POST', $harRequest2->getMethod());
    }

    public function testConstructorCreatesClone(): void
    {
        $originalHarRequest = $this->getHarFileRepository()->load(
            'www.softwareishard.com-single-entry.har'
        )->getLog()->getEntries()[0]->getRequest();

        $originalMethod = $originalHarRequest->getMethod();

        $request = new Request($originalHarRequest);

        // Modifying the original should not affect the request
        $originalHarRequest->setMethod('POST');

        $this->assertEquals('POST', $originalHarRequest->getMethod());
        $this->assertEquals($originalMethod, $request->getMethod());
    }

    public function testWithProtocolVersionDoesNotModifyOriginal(): void
    {
        $original = $this->getRequest;
        $originalVersion = $original->getProtocolVersion();

        $modified = $original->withProtocolVersion('2.0');

        // Original should have the same protocol version
        $this->assertEquals($originalVersion, $original->getProtocolVersion());

        // Modified should have the new protocol version
        $this->assertEquals('2.0', $modified->getProtocolVersion());
    }
}
