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
    public function testSerialize()
    {
        $postData = (new PostData())
          ->setParams([(new Params())->setName('test')->setFileName('test'), (new Params())->setName('test')->setContentType('text/plain')]);

        $serializer = $this->getSerializer();
        $serialized = $serializer->serialize($postData, 'json');
        // We don't care about the interior of how 'params' are serialized, so
        // we just encode and decode them for the assert.
        $this->assertEquals(
          [
            'params' => json_decode($serializer->serialize($postData->getParams(), 'json'), true),
          ],
          json_decode($serialized, true)
        );

        $class = PostData::class;
        $this->assertDeserialize($serialized, $class, $postData);
    }
}
