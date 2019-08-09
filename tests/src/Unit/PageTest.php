<?php

declare(strict_types=1);

namespace Deviantintegral\Har\Tests\Unit;

use Deviantintegral\Har\Page;
use Deviantintegral\Har\PageTiming;
use Doctrine\Common\Annotations\AnnotationRegistry;
use JMS\Serializer\Handler\DateHandler;
use JMS\Serializer\Handler\HandlerRegistryInterface;
use JMS\Serializer\Naming\IdenticalPropertyNamingStrategy;
use JMS\Serializer\SerializerBuilder;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Deviantintegral\Har\Page
 */
class PageTest extends TestCase
{
    public function testSerialize()
    {
        $timings = [
          (new PageTiming()),
          (new PageTiming()),
        ];
        $page = (new Page())
          ->setComment('Test comment')
          ->setPageTimings($timings)
          ->setId('a unique id')
          ->setStartedDateTime(new \DateTime())
          ->setTitle('The page title');

        AnnotationRegistry::registerLoader('class_exists');
        $serializer = SerializerBuilder::create()
          ->setPropertyNamingStrategy(new IdenticalPropertyNamingStrategy())
          ->configureHandlers(function (HandlerRegistryInterface $registry) {
              $registry->registerSubscribingHandler(new DateHandler(Page::ISO_8601_MICROSECONDS));
          })
          ->build();

        $serialized = $serializer->serialize($page, 'json');

        $this->assertEquals(
          [
            'startedDateTime' => $page->getStartedDateTime()->format(
              Page::ISO_8601_MICROSECONDS,
            ),
            'id' => $page->getId(),
            'title' => $page->getTitle(),
            'pageTimings' => [
              [
              ],
              [
              ],
            ],
            'comment' => $page->getComment(),
          ],
          json_decode($serialized, true)
        );

        $deserialized = $serializer->deserialize($serialized, Page::class, 'json');
        $this->assertEquals($page, $deserialized);
    }
}
