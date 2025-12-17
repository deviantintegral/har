<?php

declare(strict_types=1);

namespace Deviantintegral\Har\Tests\Unit;

use Deviantintegral\Har\CacheState;
use Deviantintegral\NullDateTime\ConcreteDateTime;

/**
 * @covers \Deviantintegral\Har\CacheState
 */
class CacheStateTest extends HarTestBase
{
    public function testSerialize(): void
    {
        $serializer = $this->getSerializer();
        $expires = new ConcreteDateTime(
            new \DateTime('2024-12-31T23:59:59Z')
        );
        $cacheState = (new CacheState())
          ->setLastAccess('2024-01-01T12:00:00Z')
          ->setETag('abc123')
          ->setHitCount(5)
          ->setExpires($expires)
          ->setComment('Test cache state');
        $serialized = $serializer->serialize($cacheState, 'json');

        $deserialized = $serializer->deserialize(
            $serialized,
            CacheState::class,
            'json'
        );
        $this->assertEquals($cacheState, $deserialized);
    }

    public function testGet(): void
    {
        $cacheState = (new CacheState())
          ->setLastAccess('2024-01-01T12:00:00Z')
          ->setETag('abc123')
          ->setHitCount(10);

        $this->assertEquals('2024-01-01T12:00:00Z', $cacheState->getLastAccess());
        $this->assertEquals('abc123', $cacheState->getETag());
        $this->assertEquals(10, $cacheState->getHitCount());
    }

    public function testDefaultHitCount(): void
    {
        $cacheState = new CacheState();
        $this->assertEquals(0, $cacheState->getHitCount());
    }
}
