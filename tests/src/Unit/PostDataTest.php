<?php

declare(strict_types=1);

namespace Deviantintegral\Har\Tests\Unit;

use Deviantintegral\Har\Params;
use Deviantintegral\Har\PostData;

/**
 * @covers \Deviantintegral\Har\PostData
 */
class PostDataTest extends HarTestBase
{
    public function testSerialize(): void
    {
        $postData = (new PostData())
            ->setParams(
                [
                    (new Params())->setName('test')->setFileName('test'),
                    (new Params())->setName('test')->setContentType(
                        'text/plain'
                    ),
                ]
            );

        $serializer = $this->getSerializer();
        $serialized = $serializer->serialize($postData, 'json');
        // We don't care about the interior of how 'params' are serialized, so
        // we just encode and decode them for the assert.
        $this->assertEquals(
            [
                'params' => json_decode(
                    $serializer->serialize($postData->getParams(), 'json'),
                    true
                ),
            ],
            json_decode($serialized, true)
        );

        $class = PostData::class;
        $this->assertDeserialize($serialized, $class, $postData);
    }

    public function testGetBodySizeWithNoData(): void
    {
        $postData = new PostData();
        // Test that getBodySize returns exactly 0 when there are no params and no text
        // This kills IncrementInteger mutation (0 -> 1) and DecrementInteger mutation (0 -> -1)
        $this->assertSame(0, $postData->getBodySize());
    }

    public function testGetBodySizeWithText(): void
    {
        $postData = (new PostData())->setText('test content');
        // The body size should be the length of the text
        $this->assertSame(12, $postData->getBodySize());
    }

    public function testGetBodySizeWithParams(): void
    {
        $postData = (new PostData())
            ->setParams([
                (new Params())->setName('key1')->setValue('value1'),
                (new Params())->setName('key2')->setValue('value2'),
            ]);
        // The body size should be the length of the query string: key1=value1&key2=value2
        $this->assertSame(23, $postData->getBodySize());
    }
}
