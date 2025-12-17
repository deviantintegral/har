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

        $deserialized = $serializer->deserialize(
            $serialized,
            Browser::class,
            'json'
        );
        $this->assertEquals($browser, $deserialized);
    }
}
