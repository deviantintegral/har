<?php

declare(strict_types=1);

namespace Deviantintegral\Har\Tests\Unit;

use Deviantintegral\Har\Header;

/**
 * @covers \Deviantintegral\Har\Header
 * @covers \Deviantintegral\Har\SharedFields\NameTrait
 * @covers \Deviantintegral\Har\SharedFields\NameValueTrait
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
        $serialized = $serializer->serialize($header, 'json');
        $this->assertEquals([
          'name' => 'Host',
            'value' => 'www.example.com',
            'comment' => 'Test value',
        ], json_decode($serialized, true));

        $deserialized = $serializer->deserialize($serialized, Header::class, 'json');
        $this->assertEquals($header, $deserialized);
    }
}
