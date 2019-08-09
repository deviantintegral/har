<?php

declare(strict_types=1);

namespace Deviantintegral\Har\Tests\Unit;

use Deviantintegral\Har\PageTiming;
use Doctrine\Common\Annotations\AnnotationRegistry;
use JMS\Serializer\Naming\IdenticalPropertyNamingStrategy;
use JMS\Serializer\SerializerBuilder;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Deviantintegral\Har\PageTiming
 */
class PageTimingTest extends TestCase
{
    public function testSerialize()
    {
        AnnotationRegistry::registerLoader('class_exists');
        $serializer = SerializerBuilder::create()
          ->setPropertyNamingStrategy(new IdenticalPropertyNamingStrategy())
          ->build();

        $creator = (new PageTiming())
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
          PageTiming::class,
          'json'
        );
        $this->assertEquals($creator, $deserialized);
    }
}
