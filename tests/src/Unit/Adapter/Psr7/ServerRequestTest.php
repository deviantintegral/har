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

        // Verify the HAR request has empty post data params
        $harRequest = $new->getHarRequest();
        $this->assertTrue($harRequest->hasPostData());
        $this->assertFalse($harRequest->getPostData()->hasParams());
    }

    public function testWithParsedBodyArraySetsHarParams(): void
    {
        $new = $this->serverRequest->withParsedBody(['key1' => 'value1', 'key2' => 'value2']);

        // Verify the HAR request has the correct params
        $harRequest = $new->getHarRequest();
        $this->assertTrue($harRequest->hasPostData());
        $params = $harRequest->getPostData()->getParams();
        $this->assertCount(2, $params);
        $this->assertEquals('key1', $params[0]->getName());
        $this->assertEquals('value1', $params[0]->getValue());
        $this->assertEquals('key2', $params[1]->getName());
        $this->assertEquals('value2', $params[1]->getValue());
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

    public function testWithUriSetsHostHeaderByDefault(): void
    {
        $newUri = new Uri('http://newhost.example.com/path');

        // Call withUri without $preserveHost parameter (defaults to false)
        $modified = $this->serverRequest->withUri($newUri);

        // The Host header should be updated to the new URI's host
        $this->assertEquals(['newhost.example.com'], $modified->getHeader('Host'));
    }

    public function testWithUriPreservesHostWhenRequested(): void
    {
        $originalHost = $this->serverRequest->getHeader('Host');
        $newUri = new Uri('http://newhost.example.com/path');

        // Call withUri with $preserveHost = true
        $modified = $this->serverRequest->withUri($newUri, true);

        // The Host header should remain unchanged
        $this->assertEquals($originalHost, $modified->getHeader('Host'));
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

        // Verify HAR params are actually set when data is an object
        $harRequest = $new->getHarRequest();
        $this->assertTrue($harRequest->hasPostData());
        $this->assertTrue($harRequest->getPostData()->hasParams());
        $params = $harRequest->getPostData()->getParams();
        $this->assertCount(2, $params);
    }

    public function testWithParsedBodyOnlyProcessesArraysAndObjects(): void
    {
        // Verify that withParsedBody only sets params for arrays and objects
        // Test with array
        $arrayRequest = $this->serverRequest->withParsedBody(['key' => 'value']);
        $this->assertTrue($arrayRequest->getHarRequest()->getPostData()->hasParams());

        // Test with object
        $obj = new \stdClass();
        $obj->key = 'value';
        $objectRequest = $this->serverRequest->withParsedBody($obj);
        $this->assertTrue($objectRequest->getHarRequest()->getPostData()->hasParams());

        // Test with null - should clear params
        $nullRequest = $this->serverRequest->withParsedBody(null);
        $this->assertFalse($nullRequest->getHarRequest()->getPostData()->hasParams());
    }

    public function testWithParsedBodyLogicalOrCondition(): void
    {
        // This test kills LogicalOr mutations by verifying the exact behavior
        // of the condition: if (is_array($data) || is_object($data))
        // We must verify the actual param names/values, not just counts,
        // because the cloned request inherits existing params.

        // When data is an array, params should be set with THE NEW values
        $arrayData = ['arrayKey1' => 'arrayVal1', 'arrayKey2' => 'arrayVal2'];
        $arrayRequest = $this->serverRequest->withParsedBody($arrayData);
        $harRequest = $arrayRequest->getHarRequest();
        $this->assertTrue($harRequest->hasPostData(), 'Array data should create post data');
        $this->assertTrue($harRequest->getPostData()->hasParams(), 'Array data should set params');
        $params = $harRequest->getPostData()->getParams();
        $this->assertCount(2, $params, 'Array should result in 2 params');
        // Verify the actual param names to ensure NEW params were set
        $this->assertEquals('arrayKey1', $params[0]->getName(), 'First param name must match array key');
        $this->assertEquals('arrayVal1', $params[0]->getValue(), 'First param value must match array value');

        // When data is an object, params should be set with THE NEW values
        // This kills LogicalOrSingleSubExprNegation mutation where objects would skip the block
        $objectData = new \stdClass();
        $objectData->objKey1 = 'objVal1';
        $objectData->objKey2 = 'objVal2';
        $objectRequest = $this->serverRequest->withParsedBody($objectData);
        $harRequest = $objectRequest->getHarRequest();
        $this->assertTrue($harRequest->hasPostData(), 'Object data should create post data');
        $this->assertTrue($harRequest->getPostData()->hasParams(), 'Object data should set params');
        $params = $harRequest->getPostData()->getParams();
        $this->assertCount(2, $params, 'Object should result in 2 params');
        // Verify the actual param names to ensure NEW params were set (not inherited)
        $this->assertEquals('objKey1', $params[0]->getName(), 'First param name must match object property');
        $this->assertEquals('objVal1', $params[0]->getValue(), 'First param value must match object property value');

        // When data is null, params should NOT be set (should create empty PostData)
        $nullRequest = $this->serverRequest->withParsedBody(null);
        $harRequest = $nullRequest->getHarRequest();
        $this->assertTrue($harRequest->hasPostData(), 'Null should create post data');
        $this->assertFalse($harRequest->getPostData()->hasParams(), 'Null should NOT set params');
        // Verify there are actually NO params (empty array)
        $this->assertEmpty($harRequest->getPostData()->getParams(), 'Null should result in empty params array');

        // This test specifically kills these mutants:
        // 1. LogicalOrAllSubExprNegation: if (!is_array($data) || !is_object($data))
        //    Would be true for null, causing it to try setting params from null (foreach warning)
        // 2. LogicalOrSingleSubExprNegation: if (is_array($data) || !is_object($data))
        //    Would be false for objects, so they wouldn't set params (param names would be old)
    }

    public function testWithParsedBodyObjectFromCleanRequest(): void
    {
        // This test specifically kills LogicalOrSingleSubExprNegation by starting
        // from a request with NO existing params, so we can verify object data sets params
        $cleanRequest = (new Request())
            ->setMethod('GET')
            ->setUrl(new Uri('https://www.example.com/'));

        $serverRequest = new ServerRequest($cleanRequest);

        // Verify no existing parsed body
        $this->assertNull($serverRequest->getParsedBody());

        // Call withParsedBody with an object
        $objectData = new \stdClass();
        $objectData->key1 = 'value1';
        $objectData->key2 = 'value2';

        $newRequest = $serverRequest->withParsedBody($objectData);

        // Verify params were set from the object
        // If LogicalOrSingleSubExprNegation mutation is applied (is_array || !is_object),
        // then for objects: false || !true = false, and the block would be skipped,
        // resulting in NO params being set
        $harRequest = $newRequest->getHarRequest();
        $this->assertTrue($harRequest->hasPostData(), 'Object data must create post data');
        $this->assertTrue($harRequest->getPostData()->hasParams(), 'Object data must set params');

        $params = $harRequest->getPostData()->getParams();
        $this->assertCount(2, $params, 'Must have exactly 2 params from object');
        $this->assertEquals('key1', $params[0]->getName());
        $this->assertEquals('value1', $params[0]->getValue());
        $this->assertEquals('key2', $params[1]->getName());
        $this->assertEquals('value2', $params[1]->getValue());
    }

    public function testWithParsedBodyNullFromCleanRequest(): void
    {
        // This test kills LogicalOrAllSubExprNegation by verifying null doesn't enter
        // the params-setting block. Start from a clean request to isolate behavior.
        $cleanRequest = (new Request())
            ->setMethod('GET')
            ->setUrl(new Uri('https://www.example.com/'));

        $serverRequest = new ServerRequest($cleanRequest);

        // Set error handler to detect if foreach on null is attempted
        // If LogicalOrAllSubExprNegation is applied (!is_array || !is_object),
        // then for null: !false || !false = true, entering the block and
        // attempting foreach on null
        $warningTriggered = false;
        $previousHandler = set_error_handler(function ($errno, $errstr) use (&$warningTriggered) {
            if (str_contains($errstr, 'foreach')) {
                $warningTriggered = true;
            }

            return false; // Allow normal error handling to continue
        });

        try {
            $newRequest = $serverRequest->withParsedBody(null);

            // Should NOT have triggered a foreach warning
            $this->assertFalse($warningTriggered, 'withParsedBody(null) should not attempt foreach');

            // Verify the result is correct
            $this->assertNull($newRequest->getParsedBody());
        } finally {
            restore_error_handler();
        }
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

    public function testGetCookieParamsWithMultipleCookies(): void
    {
        // Create a HAR request with multiple cookies
        $harRequest = (new Request())
            ->setMethod('GET')
            ->setUrl(new Uri('https://www.example.com/'))
            ->setCookies([
                (new Cookie())->setName('cookie1')->setValue('value1'),
                (new Cookie())->setName('cookie2')->setValue('value2'),
                (new Cookie())->setName('cookie3')->setValue('value3'),
            ]);

        $serverRequest = new ServerRequest($harRequest);
        $cookies = $serverRequest->getCookieParams();

        // Verify all cookies are returned, not just one
        $this->assertCount(3, $cookies);
        $this->assertEquals('value1', $cookies['cookie1']);
        $this->assertEquals('value2', $cookies['cookie2']);
        $this->assertEquals('value3', $cookies['cookie3']);
    }

    public function testGetQueryParamsWithMultipleParams(): void
    {
        // Create a HAR request with multiple query parameters
        $harRequest = (new Request())
            ->setMethod('GET')
            ->setUrl(new Uri('https://www.example.com/'))
            ->setQueryString([
                (new Params())->setName('param1')->setValue('value1'),
                (new Params())->setName('param2')->setValue('value2'),
                (new Params())->setName('param3')->setValue('value3'),
            ]);

        $serverRequest = new ServerRequest($harRequest);
        $queryParams = $serverRequest->getQueryParams();

        // Verify all query parameters are returned, not just one
        $this->assertCount(3, $queryParams);
        $this->assertEquals('value1', $queryParams['param1']);
        $this->assertEquals('value2', $queryParams['param2']);
        $this->assertEquals('value3', $queryParams['param3']);
    }

    /**
     * Kill LogicalOrAllSubExprNegation mutation in withParsedBody().
     *
     * Original: if (is_array($data) || is_object($data))
     * Mutant: if (!is_array($data) || !is_object($data))
     *
     * For null: original = false||false = false (skips block)
     *           mutant = true||true = true (enters block, warning on foreach)
     *
     * When mutant is applied, foreach(null) triggers E_WARNING which fails the
     * test due to failOnWarning="true" in phpunit.xml.dist
     */
    public function testWithParsedBodyNullKillsLogicalOrAllSubExprNegation(): void
    {
        // Start with a request that has existing post params to verify they get cleared
        $harRequest = (new Request())
            ->setMethod('POST')
            ->setUrl(new Uri('https://www.example.com/'))
            ->setPostData(
                (new PostData())->setParams([
                    (new Params())->setName('existing')->setValue('value'),
                ])
            );

        $serverRequest = new ServerRequest($harRequest);

        // Verify pre-condition: there are existing params
        $this->assertTrue($serverRequest->getHarRequest()->getPostData()->hasParams());

        // Call withParsedBody(null) - this must NOT trigger a warning
        // If mutant is applied: foreach(null as ...) triggers E_WARNING
        $result = $serverRequest->withParsedBody(null);

        // The null branch should have been taken, clearing the params
        $this->assertNull($result->getParsedBody());
        $this->assertFalse($result->getHarRequest()->getPostData()->hasParams());
    }
}
