<?php

declare(strict_types=1);

namespace Deviantintegral\Har\Tests\Unit;

use Deviantintegral\Har\Repository\HarFileRepository;
use Deviantintegral\Har\Serializer;
use JMS\Serializer\Exception\RuntimeException;

/**
 * Tests that HAR files missing the required 'log' key throw an exception.
 */
class MissingLogKeyTest extends HarTestBase
{
    /**
     * Tests that deserializeHar() throws RuntimeException when 'log' key is missing.
     *
     * Before this fix, the HAR would deserialize successfully but accessing
     * the log property would throw an Error about uninitialized property.
     * Now, deserializeHar() validates and throws a proper exception immediately.
     */
    public function testMissingLogKeyThrowsException(): void
    {
        $repository = new HarFileRepository(__DIR__.'/../../fixtures/edge-cases');
        $serializer = new Serializer();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('HAR file must contain a "log" key');

        $json = $repository->loadJson('missing-log.har');
        $serializer->deserializeHar($json);
    }

    /**
     * Tests that deserializeHar() throws RuntimeException for invalid JSON structure.
     */
    public function testEmptyJsonThrowsException(): void
    {
        $serializer = new Serializer();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('HAR file must contain a "log" key');

        $serializer->deserializeHar('{}');
    }

    /**
     * Tests that deserializeHar() throws RuntimeException when JSON is not an object.
     */
    public function testNonObjectJsonThrowsException(): void
    {
        $serializer = new Serializer();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('HAR file must contain a "log" key');

        $serializer->deserializeHar('[]');
    }
}
