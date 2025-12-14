<?php

declare(strict_types=1);

namespace Deviantintegral\Har\Tests\Unit;

use Deviantintegral\Har\Browser;
use Deviantintegral\Har\Creator;
use Deviantintegral\Har\Log;

/**
 * @covers \Deviantintegral\Har\Log
 */
class LogTest extends HarTestBase
{
    public function testSerialize()
    {
        $serializer = $this->getSerializer();

        $creator = (new Creator())
          ->setName('TestCreator')
          ->setVersion('1.0');

        $browser = (new Browser())
          ->setName('TestBrowser')
          ->setVersion('2.0');

        $log = (new Log())
          ->setVersion('1.2')
          ->setCreator($creator)
          ->setBrowser($browser)
          ->setPages([])
          ->setEntries([])
          ->setComment('Test log');

        $serialized = $serializer->serialize($log, 'json');
        $decoded = json_decode($serialized, true);

        $this->assertEquals('1.2', $decoded['version']);
        $this->assertEquals('TestCreator', $decoded['creator']['name']);
        $this->assertEquals('TestBrowser', $decoded['browser']['name']);
        $this->assertEquals('Test log', $decoded['comment']);

        $deserialized = $serializer->deserialize(
            $serialized,
            Log::class,
            'json'
        );
        $this->assertEquals($log, $deserialized);
    }

    public function testGettersAndSetters()
    {
        $creator = (new Creator())
          ->setName('TestCreator')
          ->setVersion('1.0');

        $browser = (new Browser())
          ->setName('TestBrowser')
          ->setVersion('2.0');

        $pages = [];
        $entries = [];

        $log = (new Log())
          ->setVersion('1.2')
          ->setCreator($creator)
          ->setBrowser($browser)
          ->setPages($pages)
          ->setEntries($entries)
          ->setComment('Test comment');

        $this->assertEquals('1.2', $log->getVersion());
        $this->assertSame($creator, $log->getCreator());
        $this->assertSame($browser, $log->getBrowser());
        $this->assertEquals($pages, $log->getPages());
        $this->assertEquals($entries, $log->getEntries());
        $this->assertEquals('Test comment', $log->getComment());
    }

    public function testIso8601MicrosecondsConstant()
    {
        $this->assertEquals('Y-m-d\TH:i:s.uT', Log::ISO_8601_MICROSECONDS);
    }
}
