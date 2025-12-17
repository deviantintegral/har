<?php

declare(strict_types=1);

namespace Deviantintegral\Har\Tests\Unit;

use Deviantintegral\Har\Browser;

/**
 * @covers \Deviantintegral\Har\Browser
 */
class BrowserTest extends HarTestBase
{
    public function testSerialize(): void
    {
        $serializer = $this->getSerializer();
        $browser = (new Browser())
          ->setName('BrowserTest')
          ->setVersion('1.0')
          ->setComment('Test case');
        $serialized = $serializer->serialize($browser, 'json');
        $this->assertEquals(
            [
                'name' => 'BrowserTest',
                'version' => '1.0',
                'comment' => 'Test case',
            ],
            json_decode($serialized, true)
        );

        $deserialized = $serializer->deserialize(
            $serialized,
            Browser::class,
            'json'
        );
        $this->assertEquals($browser, $deserialized);
    }
}
