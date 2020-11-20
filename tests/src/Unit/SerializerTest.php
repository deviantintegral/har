<?php

declare(strict_types=1);

namespace Deviantintegral\Har\Tests\Unit;

use Deviantintegral\Har\Har;

class SerializerTest extends HarTestBase
{
    /**
     * Test that files containing a byte order mark can be loaded.
     */
    public function testByteOrderMark()
    {
        $repository = $this->getHarFileRepository();
        $fixture = $repository->load('18_bom.har');
        $this->assertInstanceOf(Har::class, $fixture);
    }
}
