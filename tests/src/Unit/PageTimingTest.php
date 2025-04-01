<?php

declare(strict_types=1);

namespace Deviantintegral\Har\Tests\Unit;

use Deviantintegral\Har\PageTimings;

/**
 * @covers \Deviantintegral\Har\PageTimings
 */
class PageTimingTest extends HarTestBase
{
    public function testSerialize()
    {
        $serializer = $this->getSerializer();

        $creator = (new PageTimings())
          ->setOnLoad(rand())
          ->setOnContentLoad(rand())
          ->setComment('Test case');

        $serialized = $serializer->serialize($creator, 'json');
        $this->assertEquals(
            [
                'onLoad' => $creator->getOnLoad(),
                'onContentLoad' => $creator->getOnContentLoad(),
                'comment' => $creator->getComment(),
            ],
            json_decode($serialized, true)
        );

        $deserialized = $serializer->deserialize(
            $serialized,
            PageTimings::class,
            'json'
        );
        $this->assertEquals($creator, $deserialized);
    }
}
