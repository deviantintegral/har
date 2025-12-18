<?php

declare(strict_types=1);

namespace Deviantintegral\Har\Tests\Unit;

use Deviantintegral\Har\Serializer;
use JMS\Serializer\Exception\RuntimeException;

/**
 * Tests that HAR files missing the required 'log' key throw an exception.
 */
class MissingLogKeyTest extends HarTestBase
{
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
