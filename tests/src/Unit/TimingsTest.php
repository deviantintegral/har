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

    public function testHasBlocked()
    {
        $timings = new Timings();

        // Default value is -1, so hasBlocked() should return false
        $this->assertFalse($timings->hasBlocked());
        $this->assertEquals(-1, $timings->getBlocked());

        // After setting a value, hasBlocked() should return true
        $timings->setBlocked(100.5);
        $this->assertTrue($timings->hasBlocked());
        $this->assertEquals(100.5, $timings->getBlocked());
    }

    public function testHasDns()
    {
        $timings = new Timings();

        // Default value is -1, so hasDns() should return false
        $this->assertFalse($timings->hasDns());
        $this->assertEquals(-1, $timings->getDns());

        // After setting a value, hasDns() should return true
        $timings->setDns(50.3);
        $this->assertTrue($timings->hasDns());
        $this->assertEquals(50.3, $timings->getDns());
    }
}
