<?php

declare(strict_types=1);

namespace Deviantintegral\Har\Tests\Unit;

class SerializerTest extends HarTestBase
{
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
