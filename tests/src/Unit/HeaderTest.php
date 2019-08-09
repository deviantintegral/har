<?php

declare(strict_types=1);

namespace Deviantintegral\Har\Tests\Unit;

use Deviantintegral\Har\Header;

/**
 * @covers \Deviantintegral\Har\Header
 */
class HeaderTest extends HarTestBase
{
    public function testSerialize()
    {
        $header = (new Header())
          ->setName('Host')
          ->setValue('www.example.com')
          ->setComment('Test value');

        $serializer = $this->getSerializer();
        $this->assertEquals([
          'name' => 'Host',
            'value' => 'www.example.com',
            'comment' => 'Test value',
        ], json_decode($serializer->serialize($header, 'json'), true));
    }
}
