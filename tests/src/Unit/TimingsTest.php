<?php

declare(strict_types=1);

namespace Deviantintegral\Har\Tests\Unit;

use Deviantintegral\Har\Timings;

/**
 * @covers \Deviantintegral\Har\Timings
 */
class TimingsTest extends HarTestBase
{
    public function testSerialize()
    {
        $serializer = $this->getSerializer();

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
