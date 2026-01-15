<?php

declare(strict_types=1);

namespace Deviantintegral\Har\Tests\Unit;

use Deviantintegral\Har\Entry;
use Deviantintegral\Har\Har;
use Deviantintegral\Har\HarRecorder;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Uri;
use PHPUnit\Framework\Attributes\CoversClass;
use Psr\Http\Client\ClientInterface;

#[CoversClass(HarRecorder::class)]
class HarRecorderTest extends HarTestBase
{
    public function testSendRequestDelegatesToInnerClient(): void
    {
        $request = new Request('GET', new Uri('https://example.com/api'));
        $expectedResponse = new Response(200, [], 'response body');

        $innerClient = $this->createMock(ClientInterface::class);
        $innerClient->expects($this->once())
            ->method('sendRequest')
            ->with($request)
            ->willReturn($expectedResponse);

        $recorder = new HarRecorder($innerClient);
        $response = $recorder->sendRequest($request);

        $this->assertSame($expectedResponse, $response);
    }

    public function testSendRequestRecordsEntry(): void
    {
        $request = new Request('POST', new Uri('https://example.com/api'), [], 'request body');
        $expectedResponse = new Response(201, ['Content-Type' => 'application/json'], '{"id": 1}');

        $innerClient = $this->createMock(ClientInterface::class);
        $innerClient->method('sendRequest')->willReturn($expectedResponse);

        $recorder = new HarRecorder($innerClient);
        $recorder->sendRequest($request);

        $entries = $recorder->getHar()->getLog()->getEntries();
        $this->assertCount(1, $entries);
        $this->assertInstanceOf(Entry::class, $entries[0]);
    }

    public function testRecordedEntryContainsCorrectRequestData(): void
    {
        $uri = new Uri('https://example.com/api/users');
        $request = new Request('POST', $uri, ['Accept' => 'application/json'], 'test body');
        $response = new Response(200);

        $innerClient = $this->createMock(ClientInterface::class);
        $innerClient->method('sendRequest')->willReturn($response);

        $recorder = new HarRecorder($innerClient);
        $recorder->sendRequest($request);

        $entry = $recorder->getHar()->getLog()->getEntries()[0];
        $harRequest = $entry->getRequest();

        $this->assertSame('POST', $harRequest->getMethod());
        $this->assertSame((string) $uri, (string) $harRequest->getUrl());
        $this->assertTrue($harRequest->hasPostData());
        $this->assertSame('test body', $harRequest->getPostData()->getText());
    }

    public function testRecordedEntryContainsCorrectResponseData(): void
    {
        $request = new Request('GET', new Uri('https://example.com'));
        $response = new Response(
            200,
            ['Content-Type' => 'text/plain'],
            'Hello World'
        );

        $innerClient = $this->createMock(ClientInterface::class);
        $innerClient->method('sendRequest')->willReturn($response);

        $recorder = new HarRecorder($innerClient);
        $recorder->sendRequest($request);

        $entry = $recorder->getHar()->getLog()->getEntries()[0];
        $harResponse = $entry->getResponse();

        $this->assertSame(200, $harResponse->getStatus());
        $this->assertSame('OK', $harResponse->getStatusText());
        $this->assertSame('Hello World', $harResponse->getContent()->getText());
    }

    public function testRecordedEntryContainsTimingInfo(): void
    {
        $request = new Request('GET', new Uri('https://example.com'));
        $response = new Response(200);

        $innerClient = $this->createMock(ClientInterface::class);
        $innerClient->method('sendRequest')->willReturn($response);

        $recorder = new HarRecorder($innerClient);
        $recorder->sendRequest($request);

        $entry = $recorder->getHar()->getLog()->getEntries()[0];

        // Time should be non-negative and reasonable (< 1 second for a mocked instant request)
        // This catches mutations that would add timestamps instead of subtract, or use wrong divisors
        $this->assertGreaterThanOrEqual(0, $entry->getTime());
        $this->assertLessThan(1000, $entry->getTime());
        $this->assertInstanceOf(\DateTimeInterface::class, $entry->getStartedDateTime());

        $timings = $entry->getTimings();
        $this->assertSame(0.0, $timings->getSend());
        $this->assertGreaterThanOrEqual(0, $timings->getWait());
        $this->assertLessThan(1000, $timings->getWait());
        $this->assertSame(0.0, $timings->getReceive());
    }

    public function testGetHarReturnsValidHarObject(): void
    {
        $request = new Request('GET', new Uri('https://example.com'));
        $response = new Response(200);

        $innerClient = $this->createMock(ClientInterface::class);
        $innerClient->method('sendRequest')->willReturn($response);

        $recorder = new HarRecorder($innerClient);
        $recorder->sendRequest($request);

        $har = $recorder->getHar();

        $this->assertInstanceOf(Har::class, $har);
        $this->assertSame('1.2', $har->getLog()->getVersion());
        $this->assertCount(1, $har->getLog()->getEntries());
    }

    public function testGetHarWithCustomCreator(): void
    {
        $innerClient = $this->createMock(ClientInterface::class);
        $innerClient->method('sendRequest')->willReturn(new Response(200));

        $recorder = new HarRecorder($innerClient, 'MyApp', '2.5.0');
        $recorder->sendRequest(new Request('GET', new Uri('https://example.com')));

        $har = $recorder->getHar();
        $creator = $har->getLog()->getCreator();

        $this->assertSame('MyApp', $creator->getName());
        $this->assertSame('2.5.0', $creator->getVersion());
    }

    public function testDefaultCreatorValues(): void
    {
        $innerClient = $this->createMock(ClientInterface::class);
        $innerClient->method('sendRequest')->willReturn(new Response(200));

        $recorder = new HarRecorder($innerClient);
        $recorder->sendRequest(new Request('GET', new Uri('https://example.com')));

        $har = $recorder->getHar();
        $creator = $har->getLog()->getCreator();

        $this->assertSame('deviantintegral/har', $creator->getName());
        $this->assertSame('1.0', $creator->getVersion());
    }

    public function testMultipleRequestsAreRecorded(): void
    {
        $innerClient = $this->createMock(ClientInterface::class);
        $innerClient->method('sendRequest')->willReturn(new Response(200));

        $recorder = new HarRecorder($innerClient);
        $recorder->sendRequest(new Request('GET', new Uri('https://example.com/1')));
        $recorder->sendRequest(new Request('POST', new Uri('https://example.com/2')));
        $recorder->sendRequest(new Request('DELETE', new Uri('https://example.com/3')));

        $entries = $recorder->getHar()->getLog()->getEntries();
        $this->assertCount(3, $entries);
        $this->assertCount(3, $recorder->getHar()->getLog()->getEntries());

        $this->assertSame('GET', $entries[0]->getRequest()->getMethod());
        $this->assertSame('POST', $entries[1]->getRequest()->getMethod());
        $this->assertSame('DELETE', $entries[2]->getRequest()->getMethod());
    }

    public function testRecordedHarIsSerializable(): void
    {
        $uri = new Uri('https://example.com/api');
        $request = new Request('POST', $uri, ['Content-Type' => 'application/json'], '{"test": true}');
        $response = new Response(201, ['Content-Type' => 'application/json'], '{"id": 123}');

        $innerClient = $this->createMock(ClientInterface::class);
        $innerClient->method('sendRequest')->willReturn($response);

        $recorder = new HarRecorder($innerClient);
        $recorder->sendRequest($request);

        $har = $recorder->getHar();
        $serializer = $this->getSerializer();

        $json = $serializer->serialize($har, 'json');
        $this->assertJson($json);

        $data = json_decode($json, true);
        $this->assertIsArray($data);
        $this->assertArrayHasKey('log', $data);
        $this->assertIsArray($data['log']);
        $this->assertSame('1.2', $data['log']['version']);
        $this->assertArrayHasKey('entries', $data['log']);
        $this->assertIsArray($data['log']['entries']);
        $this->assertCount(1, $data['log']['entries']);
    }

    public function testRecordedEntryHasCacheObject(): void
    {
        $innerClient = $this->createMock(ClientInterface::class);
        $innerClient->method('sendRequest')->willReturn(new Response(200));

        $recorder = new HarRecorder($innerClient);
        $recorder->sendRequest(new Request('GET', new Uri('https://example.com')));

        $entry = $recorder->getHar()->getLog()->getEntries()[0];

        $this->assertInstanceOf(\Deviantintegral\Har\Cache::class, $entry->getCache());
    }

    public function testGetHarWithNoEntriesReturnsEmptyHar(): void
    {
        $innerClient = $this->createMock(ClientInterface::class);
        $recorder = new HarRecorder($innerClient);

        $har = $recorder->getHar();

        $this->assertInstanceOf(Har::class, $har);
        $this->assertSame('1.2', $har->getLog()->getVersion());
        $this->assertEmpty($har->getLog()->getEntries());
    }

    public function testClientExceptionIsPropagated(): void
    {
        $exception = new class extends \Exception implements \Psr\Http\Client\ClientExceptionInterface {};

        $innerClient = $this->createMock(ClientInterface::class);
        $innerClient->method('sendRequest')
            ->willThrowException($exception);

        $recorder = new HarRecorder($innerClient);

        $this->expectException(\Psr\Http\Client\ClientExceptionInterface::class);
        $recorder->sendRequest(new Request('GET', new Uri('https://example.com')));
    }

    public function testFailedRequestIsNotRecorded(): void
    {
        $exception = new class extends \Exception implements \Psr\Http\Client\ClientExceptionInterface {};

        $innerClient = $this->createMock(ClientInterface::class);
        $innerClient->method('sendRequest')
            ->willThrowException($exception);

        $recorder = new HarRecorder($innerClient);

        try {
            $recorder->sendRequest(new Request('GET', new Uri('https://example.com')));
        } catch (\Psr\Http\Client\ClientExceptionInterface) {
            // Expected
        }

        $this->assertEmpty($recorder->getHar()->getLog()->getEntries());
    }

    public function testCountReturnsCorrectNumber(): void
    {
        $innerClient = $this->createMock(ClientInterface::class);
        $innerClient->method('sendRequest')->willReturn(new Response(200));

        $recorder = new HarRecorder($innerClient);

        $this->assertSame(0, $recorder->count());

        $recorder->sendRequest(new Request('GET', new Uri('https://example.com')));
        $this->assertSame(1, $recorder->count());

        $recorder->sendRequest(new Request('GET', new Uri('https://example.com')));
        $this->assertSame(2, $recorder->count());
    }

    public function testClearRemovesAllEntries(): void
    {
        $innerClient = $this->createMock(ClientInterface::class);
        $innerClient->method('sendRequest')->willReturn(new Response(200));

        $recorder = new HarRecorder($innerClient);
        $recorder->sendRequest(new Request('GET', new Uri('https://example.com/1')));
        $recorder->sendRequest(new Request('GET', new Uri('https://example.com/2')));

        $this->assertSame(2, $recorder->count());

        $recorder->clear();

        $this->assertSame(0, $recorder->count());
        $this->assertEmpty($recorder->getHar()->getLog()->getEntries());
        $this->assertEmpty($recorder->getHar()->getLog()->getEntries());
    }
}
