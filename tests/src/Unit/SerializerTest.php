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
}
