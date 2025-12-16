<?php

declare(strict_types=1);

namespace Deviantintegral\Har\Tests\Unit;

use Deviantintegral\Har\Har;
use Deviantintegral\Har\Serializer;
use JMS\Serializer\Exception\RuntimeException as JMSRuntimeException;

/**
 * Tests edge cases and error handling for HAR file parsing.
 *
 * @covers \Deviantintegral\Har\Serializer
 * @covers \Deviantintegral\Har\Repository\HarFileRepository
 */
class EdgeCaseTest extends HarTestBase
{
    /**
     * Get repository for edge case fixtures.
     */
    private function getEdgeCaseRepository(): \Deviantintegral\Har\Repository\HarFileRepository
    {
        return new \Deviantintegral\Har\Repository\HarFileRepository(__DIR__.'/../../fixtures/edge-cases');
    }

    /**
     * Tests that invalid JSON in HAR files throws an exception.
     */
    public function testInvalidJsonThrowsException(): void
    {
        $repository = $this->getEdgeCaseRepository();
        $serializer = new Serializer();

        $this->expectException(JMSRuntimeException::class);
        $json = $repository->loadJson('invalid-json.har');
        $serializer->deserializeHar($json);
    }

    /**
     * Tests that incomplete/truncated JSON throws an exception.
     */
    public function testIncompleteJsonThrowsException(): void
    {
        $repository = $this->getEdgeCaseRepository();
        $serializer = new Serializer();

        $this->expectException(JMSRuntimeException::class);
        $json = $repository->loadJson('incomplete-json.har');
        $serializer->deserializeHar($json);
    }

    /**
     * Tests that HAR files missing the required "log" key result in uninitialized property.
     * Accessing the log property should throw an Error.
     */
    public function testMissingLogKeyResultsInUninitializedProperty(): void
    {
        $repository = $this->getEdgeCaseRepository();
        $serializer = new Serializer();

        $json = $repository->loadJson('missing-log.har');
        $har = $serializer->deserializeHar($json);

        // The HAR deserializes successfully, but the log property is uninitialized
        // Accessing it should throw an Error
        $this->expectException(\Error::class);
        $this->expectExceptionMessageMatches('/initialization/');
        // @phpstan-ignore method.resultUnused
        $har->getLog();
    }

    /**
     * Tests that HAR files with invalid structure (wrong types) throw an exception.
     */
    public function testInvalidStructureThrowsException(): void
    {
        $repository = $this->getEdgeCaseRepository();
        $serializer = new Serializer();

        $this->expectException(JMSRuntimeException::class);
        $json = $repository->loadJson('invalid-structure.har');
        $serializer->deserializeHar($json);
    }

    /**
     * Tests that HAR files with malformed entries throw an exception.
     */
    public function testMalformedEntryThrowsException(): void
    {
        $repository = $this->getEdgeCaseRepository();
        $serializer = new Serializer();

        $this->expectException(JMSRuntimeException::class);
        $json = $repository->loadJson('malformed-entry.har');
        $serializer->deserializeHar($json);
    }

    /**
     * Tests that HAR files with null values where objects are expected throw a TypeError.
     */
    public function testNullValuesThrowException(): void
    {
        $repository = $this->getEdgeCaseRepository();
        $serializer = new Serializer();

        $this->expectException(\TypeError::class);
        $json = $repository->loadJson('null-values.har');
        $serializer->deserializeHar($json);
    }

    /**
     * Tests that empty but valid HAR files can be loaded successfully.
     */
    public function testEmptyLogLoadsSuccessfully(): void
    {
        $repository = $this->getEdgeCaseRepository();
        $har = $repository->load('empty-log.har');

        $this->assertInstanceOf(Har::class, $har);
        $this->assertSame('1.2', $har->getLog()->getVersion());
        $this->assertSame('test', $har->getLog()->getCreator()->getName());
        $this->assertSame('1.0', $har->getLog()->getCreator()->getVersion());
        $this->assertEmpty($har->getLog()->getEntries());
    }

    /**
     * Tests that minimal valid HAR files can be loaded and serialized.
     */
    public function testMinimalValidHarLoadsSuccessfully(): void
    {
        $repository = $this->getEdgeCaseRepository();
        $har = $repository->load('minimal-valid.har');

        $this->assertInstanceOf(Har::class, $har);
        $this->assertSame('1.2', $har->getLog()->getVersion());
        $this->assertSame('minimal-test', $har->getLog()->getCreator()->getName());
        $this->assertSame('0.0.1', $har->getLog()->getCreator()->getVersion());
        $this->assertEmpty($har->getLog()->getEntries());
    }

    /**
     * Tests that minimal HAR can be reserialized without data loss.
     */
    public function testMinimalHarRoundTrip(): void
    {
        $repository = $this->getEdgeCaseRepository();
        $har = $repository->load('minimal-valid.har');

        $serializer = new Serializer();
        $serialized = $serializer->serializeHar($har);
        $decoded = json_decode($serialized, true);

        $this->assertIsArray($decoded);
        $this->assertArrayHasKey('log', $decoded);
        $this->assertArrayHasKey('version', $decoded['log']);
        $this->assertSame('1.2', $decoded['log']['version']);
        $this->assertArrayHasKey('creator', $decoded['log']);
        $this->assertSame('minimal-test', $decoded['log']['creator']['name']);
        $this->assertArrayHasKey('entries', $decoded['log']);
        $this->assertEmpty($decoded['log']['entries']);
    }

    /**
     * Tests that the existing empty file test still works (regression test).
     */
    public function testLoadJsonHandlesEmptyFile(): void
    {
        $tempDir = sys_get_temp_dir().'/har_test_edge_'.uniqid();
        mkdir($tempDir);
        $emptyFile = $tempDir.'/empty.har';
        file_put_contents($emptyFile, '');

        $repository = new \Deviantintegral\Har\Repository\HarFileRepository($tempDir);
        $this->expectException(\RuntimeException::class);
        try {
            $repository->loadJson('empty.har');
        } finally {
            unlink($emptyFile);
            rmdir($tempDir);
        }
    }

    /**
     * Tests that corrupted binary data throws an exception.
     */
    public function testCorruptedBinaryDataThrowsException(): void
    {
        $tempDir = sys_get_temp_dir().'/har_test_binary_'.uniqid();
        mkdir($tempDir);
        $binaryFile = $tempDir.'/binary.har';
        // Write random binary data
        file_put_contents($binaryFile, random_bytes(100));

        $repository = new \Deviantintegral\Har\Repository\HarFileRepository($tempDir);
        $serializer = new Serializer();

        $this->expectException(JMSRuntimeException::class);
        try {
            $json = $repository->loadJson('binary.har');
            $serializer->deserializeHar($json);
        } finally {
            unlink($binaryFile);
            rmdir($tempDir);
        }
    }

    /**
     * Tests that very large HAR files can still be loaded.
     * This creates a HAR with many entries to test memory/performance edge cases.
     */
    public function testLargeHarFileLoadsSuccessfully(): void
    {
        $tempDir = sys_get_temp_dir().'/har_test_large_'.uniqid();
        mkdir($tempDir);
        $largeFile = $tempDir.'/large.har';

        // Create a HAR with 100 minimal entries
        $entries = [];
        for ($i = 0; $i < 100; ++$i) {
            $entries[] = [
                'startedDateTime' => '2019-08-20T20:04:34.710Z',
                'time' => 0,
                'request' => [
                    'method' => 'GET',
                    'url' => 'http://example.com/page'.$i,
                    'httpVersion' => 'HTTP/1.1',
                    'headers' => [],
                    'queryString' => [],
                    'cookies' => [],
                    'headersSize' => -1,
                    'bodySize' => -1,
                ],
                'response' => [
                    'status' => 200,
                    'statusText' => 'OK',
                    'httpVersion' => 'HTTP/1.1',
                    'headers' => [],
                    'cookies' => [],
                    'content' => [
                        'size' => 0,
                        'mimeType' => 'text/html',
                    ],
                    'redirectURL' => '',
                    'headersSize' => -1,
                    'bodySize' => -1,
                ],
                'cache' => (object) [],
                'timings' => [
                    'send' => -1,
                    'wait' => -1,
                    'receive' => -1,
                ],
            ];
        }

        $harData = [
            'log' => [
                'version' => '1.2',
                'creator' => [
                    'name' => 'large-test',
                    'version' => '1.0',
                ],
                'entries' => $entries,
            ],
        ];

        file_put_contents($largeFile, json_encode($harData));

        try {
            $repository = new \Deviantintegral\Har\Repository\HarFileRepository($tempDir);
            $har = $repository->load('large.har');

            $this->assertInstanceOf(Har::class, $har);
            $this->assertCount(100, $har->getLog()->getEntries());
        } finally {
            unlink($largeFile);
            rmdir($tempDir);
        }
    }

    /**
     * Tests that HAR with only whitespace is handled correctly.
     */
    public function testWhitespaceOnlyFileThrowsException(): void
    {
        $tempDir = sys_get_temp_dir().'/har_test_whitespace_'.uniqid();
        mkdir($tempDir);
        $whitespaceFile = $tempDir.'/whitespace.har';
        file_put_contents($whitespaceFile, "   \n\t\r\n   ");

        $repository = new \Deviantintegral\Har\Repository\HarFileRepository($tempDir);
        $serializer = new Serializer();

        $this->expectException(JMSRuntimeException::class);
        try {
            $json = $repository->loadJson('whitespace.har');
            $serializer->deserializeHar($json);
        } finally {
            unlink($whitespaceFile);
            rmdir($tempDir);
        }
    }
}
