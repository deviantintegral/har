<?php

declare(strict_types=1);

namespace Deviantintegral\Har\Tests\Unit;

use Deviantintegral\Har\Cache;

class CacheTest extends HarTestBase
{
    public function testSerializeWhenEmpty(): void
    {
        $cache = (new Cache());
        $this->assertFalse($cache->hasAfterRequest());
        $this->assertNull($cache->getAfterRequest());
        $this->assertFalse($cache->hasBeforeRequest());
        $this->assertNull($cache->getBeforeRequest());
        $this->assertFalse($cache->hasComment());
    }
}
