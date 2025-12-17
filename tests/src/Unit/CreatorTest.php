<?php

declare(strict_types=1);

namespace Deviantintegral\Har\Tests\Unit;

use Deviantintegral\Har\Creator;

/**
 * @covers \Deviantintegral\Har\Creator
 */
class CreatorTest extends HarTestBase
{
    public function testSerialize(): void
    {
        $serializer = $this->getSerializer();
        $creator = (new Creator())
          ->setName('CreatorTest')
          ->setVersion('1.9')
          ->setComment('Test case');
        $serialized = $serializer->serialize($creator, 'json');

        $deserialized = $serializer->deserialize(
            $serialized,
            Creator::class,
            'json'
        );
        $this->assertEquals($creator, $deserialized);
    }
}
