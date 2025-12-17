<?php

declare(strict_types=1);

namespace Deviantintegral\Har\Tests\Unit;

use Deviantintegral\Har\Har;

class SerializerTest extends HarTestBase
{
    /**
     * Test that files containing a byte order mark can be loaded.
     */
    public function testByteOrderMark(): void
    {
        $repository = $this->getHarFileRepository();
        $fixture = $repository->load('18_bom.har');
        $this->assertInstanceOf(Har::class, $fixture);
    }

    /**
     * Test that getSerializerBuilder is publicly accessible.
     */
    public function testGetSerializerBuilderIsPublic(): void
    {
        $serializer = new \Deviantintegral\Har\Serializer();
        $builder = $serializer->getSerializerBuilder();
        $this->assertInstanceOf(\JMS\Serializer\SerializerBuilder::class, $builder);

        // Verify the builder can create a serializer
        $builtSerializer = $builder->build();
        $this->assertInstanceOf(\JMS\Serializer\SerializerInterface::class, $builtSerializer);
    }

    /**
     * Test that TruncatingDateTimeHandler is registered and truncates microseconds.
     */
    public function testTruncatingDateTimeHandlerIsRegistered(): void
    {
        $serializer = new \Deviantintegral\Har\Serializer();

        // Create a HAR with a timestamp that has > 6 digits of microseconds
        $jsonWithLongMicroseconds = json_encode([
            'log' => [
                'version' => '1.2',
                'creator' => ['name' => 'test', 'version' => '1.0'],
                'entries' => [
                    [
                        'startedDateTime' => '2024-01-01T12:00:00.123456789+00:00', // 9 digits
                        'time' => 100,
                        'request' => [
                            'method' => 'GET',
                            'url' => 'http://example.com',
                            'httpVersion' => 'HTTP/1.1',
                            'headers' => [],
                            'queryString' => [],
                            'cookies' => [],
                            'headersSize' => 0,
                            'bodySize' => 0,
                        ],
                        'response' => [
                            'status' => 200,
                            'statusText' => 'OK',
                            'httpVersion' => 'HTTP/1.1',
                            'headers' => [],
                            'cookies' => [],
                            'content' => ['size' => 0, 'mimeType' => 'text/html'],
                            'redirectURL' => '',
                            'headersSize' => 0,
                            'bodySize' => 0,
                        ],
                        'cache' => [],
                        'timings' => ['send' => 0, 'wait' => 0, 'receive' => 0],
                    ],
                ],
            ],
        ]);

        // Deserialize - this should truncate the microseconds to 6 digits
        $har = $serializer->deserializeHar($jsonWithLongMicroseconds);

        // Serialize back and verify microseconds are truncated
        $reserialized = $serializer->serializeHar($har);
        $decoded = json_decode($reserialized, true);

        $startedDateTime = $decoded['log']['entries'][0]['startedDateTime'];

        // The microseconds should be truncated to 6 digits
        // Original: .123456789, Truncated: .123456
        $this->assertStringContainsString('.123456', $startedDateTime);
        $this->assertStringNotContainsString('.123456789', $startedDateTime);
    }

    /**
     * Test that removeBOM is publicly accessible.
     */
    public function testRemoveBOMIsPublic(): void
    {
        $serializer = new \Deviantintegral\Har\Serializer();

        // Test with BOM
        $dataWithBOM = pack('CCC', 0xEF, 0xBB, 0xBF).'test data';
        $cleaned = $serializer->removeBOM($dataWithBOM);
        $this->assertEquals('test data', $cleaned);

        // Test without BOM
        $dataWithoutBOM = 'test data';
        $cleaned = $serializer->removeBOM($dataWithoutBOM);
        $this->assertEquals('test data', $cleaned);
    }
}
