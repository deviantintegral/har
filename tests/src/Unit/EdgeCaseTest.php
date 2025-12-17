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
