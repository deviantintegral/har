<?php

declare(strict_types=1);

namespace Deviantintegral\Har\Tests\Unit;

use Deviantintegral\Har\Page;
use Deviantintegral\Har\Timings;
use Doctrine\Common\Annotations\AnnotationRegistry;
use JMS\Serializer\Handler\DateHandler;
use JMS\Serializer\Handler\HandlerRegistryInterface;
use JMS\Serializer\Naming\IdenticalPropertyNamingStrategy;
use JMS\Serializer\SerializerBuilder;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Deviantintegral\Har\Timings
 */
class TimingsTest extends TestCase
{
    public function testSerialize()
    {
        AnnotationRegistry::registerLoader('class_exists');
        $serializer = SerializerBuilder::create()
          ->setPropertyNamingStrategy(new IdenticalPropertyNamingStrategy())
          ->configureHandlers(
            function (HandlerRegistryInterface $registry) {
                $registry->registerSubscribingHandler(
                  new DateHandler(Page::ISO_8601_MICROSECONDS)
                );
            }
          )
          ->build();

        $timings = (new Timings())
          ->setComment('Test comment')
          ->setBlocked(rand())
          ->setSsl(rand())
          ->setDns(rand())
          ->setReceive(rand())
          ->setSend(rand())
          ->setWait(rand());
        $timings->setConnect($timings->getSsl() + rand());

        $serialized = $serializer->serialize($timings, 'json');
        $this->assertEquals(
          [
            'blocked' => $timings->getBlocked(),
            'dns' => $timings->getDns(),
            'connect' => $timings->getConnect(),
            'send' => $timings->getSend(),
            'wait' => $timings->getWait(),
            'receive' => $timings->getReceive(),
            'ssl' => $timings->getSsl(),
            'comment' => $timings->getComment(),
          ],
          json_decode($serialized, true)
        );

        $deserialized = $serializer->deserialize($serialized, Timings::class, 'json');
        $this->assertEquals($timings, $deserialized);
    }
}
