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

    public function testGetParamsClearsText(): void
    {
        // Text and params are mutually exclusive
        $postData = new PostData();

        // First set both text and params
        $postData->setText('some text content');
        $postData->setParams([
            (new Params())->setName('key')->setValue('value'),
        ]);

        // Getting params should clear the text field
        $params = $postData->getParams();

        $this->assertNotEmpty($params);
        $this->assertFalse($postData->hasText());
        $this->assertNull($postData->getText());
    }

    public function testHasParamsReturnsTrueWhenParamsSet(): void
    {
        $postData = new PostData();
        $this->assertFalse($postData->hasParams());

        $postData->setParams([
            (new Params())->setName('key')->setValue('value'),
        ]);

        $this->assertTrue($postData->hasParams());
    }

    public function testHasParamsReturnsFalseWhenParamsEmpty(): void
    {
        $postData = new PostData();
        $postData->setParams([]);

        $this->assertFalse($postData->hasParams());
    }

    public function testGetBodySizeReturnsZeroWhenNoParams(): void
    {
        $postData = new PostData();
        $postData->setText('test');
        // Clear text by setting empty params
        $postData->setParams([]);

        // Should return 0 when hasParams() is false
        $this->assertSame(0, $postData->getBodySize());
    }

    public function testGetBodySizeCalculatesFromParamsWhenPresent(): void
    {
        $postData = new PostData();
        $postData->setParams([
            (new Params())->setName('foo')->setValue('bar'),
        ]);

        // Verify hasParams returns true
        $this->assertTrue($postData->hasParams());

        // Verify getBodySize calculates from params (foo=bar = 7 chars)
        $this->assertSame(7, $postData->getBodySize());
    }

    public function testGetBodySizeDoesNotCalculateFromParamsWhenAbsent(): void
    {
        $postData = new PostData();

        // Verify hasParams returns false
        $this->assertFalse($postData->hasParams());

        // Verify getBodySize returns 0 (doesn't try to calculate from params)
        $this->assertSame(0, $postData->getBodySize());
    }

    public function testSetTextIsPublic(): void
    {
        $postData = new PostData();

        // Verify setText is publicly accessible
        $result = $postData->setText('test content');

        // Verify it returns the PostData instance for method chaining
        $this->assertSame($postData, $result);

        // Verify the text was set
        $this->assertEquals('test content', $postData->getText());
    }
}
