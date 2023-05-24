<?php

declare(strict_types=1);

namespace Deviantintegral\Har\Tests\Unit;

use Deviantintegral\Har\Repository\HarFileRepository;
use Deviantintegral\Har\Serializer;
use PHPUnit\Framework\TestCase;

abstract class HarTestBase extends TestCase
{
    protected function getSerializer(): \JMS\Serializer\SerializerInterface
    {
        return (new Serializer())->getSerializer();
    }

    protected function assertDeserialize(
        string $serialized,
        string $class,
        $expected
    ): void {
        $serializer = $this->getSerializer();
        $deserialized = $serializer->deserialize(
            $serialized,
            $class,
            'json'
        );
        $this->assertEquals($expected, $deserialized);
    }

    protected function getHarFileRepository(
    ): HarFileRepository {
        return new HarFileRepository(__DIR__.'/../../fixtures');
    }
}
