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

    public function testGet(): void
    {
        $browser = (new Browser())
          ->setName('BrowserTest')
          ->setVersion('1.0')
          ->setComment('Test case');

        $this->assertEquals('BrowserTest', $browser->getName());
        $this->assertEquals('1.0', $browser->getVersion());
        $this->assertEquals('Test case', $browser->getComment());
    }

    public function testClone(): void
    {
        $browser = (new Browser())
          ->setName('BrowserTest')
          ->setVersion('1.0')
          ->setComment('Test case');

        $clonedBrowser = clone $browser;

        // Verify the clone is a different object
        $this->assertNotSame($browser, $clonedBrowser);

        // Verify the clone has the same property values
        $this->assertEquals($browser->getName(), $clonedBrowser->getName());
        $this->assertEquals($browser->getVersion(), $clonedBrowser->getVersion());
        $this->assertEquals($browser->getComment(), $clonedBrowser->getComment());

        // Verify modifying the clone doesn't affect the original
        $clonedBrowser->setName('ModifiedBrowser');
        $this->assertEquals('BrowserTest', $browser->getName());
        $this->assertEquals('ModifiedBrowser', $clonedBrowser->getName());
    }
}
