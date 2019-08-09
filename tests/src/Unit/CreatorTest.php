<?php

declare(strict_types=1);

namespace Deviantintegral\Har\Tests\Unit;

use Deviantintegral\Har\Creator;

/**
 * @covers \Deviantintegral\Har\Creator
 */
class CreatorTest extends HarTestBase
{
    public function testSerialize()
    {
        $serializer = $this->getSerializer();
        $creator = (new Creator())
          ->setName('CreatorTest')
          ->setVersion('1.9')
          ->setComment('Test case');
        $serialized = $serializer->serialize($creator, 'json');
        $this->assertEquals(
          [
            'name' => 'CreatorTest',
            'version' => '1.9',
            'comment' => 'Test case',
          ],
          json_decode($serialized, true)
        );

        $deserialized = $serializer->deserialize(
          $serialized,
          Creator::class,
          'json'
        );
        $this->assertEquals($creator, $deserialized);
    }

    public function testGet()
    {
        $creator = (new Creator())
          ->setName('CreatorTest')
          ->setVersion('1.9')
          ->setComment('Test case');

        $this->assertEquals('CreatorTest', $creator->getName());
        $this->assertEquals('1.9', $creator->getVersion());
        $this->assertEquals('Test case', $creator->getComment());
    }
}
