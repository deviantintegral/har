<?php

declare(strict_types=1);

namespace Deviantintegral\Har\Tests\Unit;

use Deviantintegral\Har\Log;
use Deviantintegral\Har\Page;
use Deviantintegral\Har\PageTimings;

/**
 * @covers \Deviantintegral\Har\Page
 */
class PageTest extends HarTestBase
{
    public function testSerialize()
    {
        $page = (new Page())
          ->setComment('Test comment')
          ->setPageTimings(new PageTimings())
          ->setId('a unique id')
          ->setStartedDateTime(new \DateTime())
          ->setTitle('The page title');

        $serializer = $this->getSerializer();

        $serialized = $serializer->serialize($page, 'json');

        $this->assertEquals(
            [
              'startedDateTime' => $page->getStartedDateTime()->format(
                  Log::ISO_8601_MICROSECONDS
              ),
              'id' => $page->getId(),
              'title' => $page->getTitle(),
              'pageTimings' => [],
              'comment' => $page->getComment(),
            ],
            json_decode($serialized, true)
        );

        $deserialized = $serializer->deserialize($serialized, Page::class, 'json');
        $this->assertEquals($page, $deserialized);
    }
}
