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
          ->setParams([(new Params())->setFileName('test'), (new Params())->setContentType('text/plain')]);

        $serializer = $this->getSerializer();
        $serialized = $serializer->serialize($postData, 'json');
        $this->assertEquals(
          [
            'params' => json_decode($serializer->serialize($postData->getParams(), 'json'), true)
          ],
          json_decode($serialized, true)
        );

        $deserialized = $serializer->deserialize(
          $serialized,
          PostData::class,
          'json'
        );
        $this->assertEquals($postData, $deserialized);
    }
}
