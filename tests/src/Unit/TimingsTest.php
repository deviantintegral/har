<?php

declare(strict_types=1);

namespace Deviantintegral\Har\Tests\Unit;

use Deviantintegral\Har\Timings;

/**
 * @covers \Deviantintegral\Har\Timings
 */
class TimingsTest extends HarTestBase
{
    public function testSerialize(): void
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

    public function testHasBlocked(): void
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

    public function testHasDns(): void
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

    public function testHasConnect(): void
    {
        $timings = new Timings();

        // Default value is -1, so hasConnect() should return false
        $this->assertFalse($timings->hasConnect());
        $this->assertEquals(-1, $timings->getConnect());

        // After setting a value, hasConnect() should return true
        $timings->setConnect(75.2);
        $this->assertTrue($timings->hasConnect());
        $this->assertEquals(75.2, $timings->getConnect());
    }

    public function testHasSsl(): void
    {
        $timings = new Timings();

        // Default value is -1, so hasSsl() should return false
        $this->assertFalse($timings->hasSsl());
        $this->assertEquals(-1, $timings->getSsl());

        // After setting a value, hasSsl() should return true
        $timings->setSsl(25.7);
        $this->assertTrue($timings->hasSsl());
        $this->assertEquals(25.7, $timings->getSsl());
    }

    public function testSetConnectAllowsConnectEqualToSSL(): void
    {
        $timings = new Timings();
        $timings->setSsl(100.0);

        // Connect time equal to SSL time should be allowed
        // The check is (connect < ssl), so connect >= ssl is valid
        $timings->setConnect(100.0);
        $this->assertEquals(100.0, $timings->getConnect());
    }

    public function testSetConnectThrowsExceptionWhenConnectLessThanSSL(): void
    {
        $timings = new Timings();
        $timings->setSsl(100.0);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Connect time must include SSL time');

        // Connect time less than SSL time should throw exception
        $timings->setConnect(50.0);
    }

    public function testSetConnectAllowsConnectGreaterThanSSL(): void
    {
        $timings = new Timings();
        $timings->setSsl(100.0);

        // Connect time greater than SSL time should be allowed
        $timings->setConnect(150.0);
        $this->assertEquals(150.0, $timings->getConnect());
    }

    public function testSetSendAllowsZero(): void
    {
        $timings = new Timings();

        // Send time of 0 should be allowed (not negative)
        $timings->setSend(0.0);
        $this->assertEquals(0.0, $timings->getSend());
    }

    public function testSetSendThrowsExceptionForNegativeValues(): void
    {
        $timings = new Timings();

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Send must not be negative');

        $timings->setSend(-1.0);
    }

    public function testSetWaitAllowsZero(): void
    {
        $timings = new Timings();

        // Wait time of 0 should be allowed (not negative)
        $timings->setWait(0.0);
        $this->assertEquals(0.0, $timings->getWait());
    }

    public function testSetWaitThrowsExceptionForNegativeValues(): void
    {
        $timings = new Timings();

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Wait must not be negative');

        $timings->setWait(-1.0);
    }

    public function testSetReceiveAllowsZero(): void
    {
        $timings = new Timings();

        // Receive time of 0 should be allowed (not negative)
        $timings->setReceive(0.0);
        $this->assertEquals(0.0, $timings->getReceive());
    }

    public function testSetReceiveThrowsExceptionForNegativeValues(): void
    {
        $timings = new Timings();

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Receive must not be negative');

        $timings->setReceive(-1.0);
    }
}
