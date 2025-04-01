<?php

declare(strict_types=1);

namespace Deviantintegral\Har\Tests\Unit;

use Deviantintegral\Har\QueryString;

/**
 * @covers \Deviantintegral\Har\QueryString
 */
class QueryStringTest extends HarTestBase
{
    public function testSerialize()
    {
        $query = (new QueryString())
          ->setName('Host')
          ->setValue('www.example.com')
          ->setComment('Test value');

        $serializer = $this->getSerializer();
        $this->assertEquals(
            [
                'name' => 'Host',
                'value' => 'www.example.com',
                'comment' => 'Test value',
            ],
            json_decode($serializer->serialize($query, 'json'), true)
        );
    }
}
