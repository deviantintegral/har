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

        // Verify hasParams affects getBodySize behavior
        // With params, getBodySize should calculate from params
        $this->assertGreaterThan(0, $postData->getBodySize());
    }

    public function testHasParamsReturnsFalseWhenParamsEmpty(): void
    {
        $postData = new PostData();
        $postData->setParams([]);

        $this->assertFalse($postData->hasParams());

        // Verify hasParams affects getBodySize behavior
        // Without params, getBodySize should return 0 (not calculate)
        $this->assertSame(0, $postData->getBodySize());
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

    public function testHasParamsLogicalNotMutation(): void
    {
        // This test kills the LogicalNot mutant by explicitly verifying
        // both true and false cases of hasParams()
        $postData = new PostData();

        // When params is null/empty, hasParams() should return false
        $this->assertFalse($postData->hasParams(), 'hasParams() should return false when params is empty');

        // Set params
        $postData->setParams([
            (new Params())->setName('key')->setValue('value'),
        ]);

        // When params has items, hasParams() should return true
        $this->assertTrue($postData->hasParams(), 'hasParams() should return true when params has items');

        // Clear params
        $postData->setParams([]);

        // When params is explicitly empty array, hasParams() should return false
        $this->assertFalse($postData->hasParams(), 'hasParams() should return false when params is empty array');

        // This test specifically kills the LogicalNot mutant at PostData.php:68
        // If `return !empty($this->params)` is changed to `return empty($this->params)`,
        // this test will fail because the true/false results would be inverted
    }

    public function testGetBodySizeIfNegationMutation(): void
    {
        // This test kills the IfNegation mutant by explicitly verifying
        // the behavior when hasParams() is true vs false
        $postData = new PostData();

        // When hasParams() is false, getBodySize() should skip the params calculation
        $this->assertFalse($postData->hasParams());
        $this->assertSame(0, $postData->getBodySize(), 'Should return 0 when no params');

        // Set params
        $postData->setParams([
            (new Params())->setName('foo')->setValue('bar'),
        ]);

        // When hasParams() is true, getBodySize() should calculate from params
        $this->assertTrue($postData->hasParams());
        $expectedSize = \strlen('foo=bar');  // 7
        $this->assertSame($expectedSize, $postData->getBodySize(), 'Should calculate size from params');

        // This test specifically kills the IfNegation mutant at PostData.php:73
        // If `if ($this->hasParams())` is changed to `if (!$this->hasParams())`,
        // the calculation would happen when there are no params (causing error)
        // and NOT happen when there are params (returning 0 incorrectly)
    }

    public function testGetBodySizeWithParamsReturnsCorrectSize(): void
    {
        // This test kills IfNegation by verifying the exact size returned when params exist
        // If the condition is negated, this would return 0 instead of the correct size
        $postData = new PostData();
        $postData->setParams([
            (new Params())->setName('key')->setValue('value'),
            (new Params())->setName('another')->setValue('test'),
        ]);

        // key=value&another=test = 22 characters
        $expectedSize = \strlen('key=value&another=test');
        $actualSize = $postData->getBodySize();

        // The IfNegation mutant would skip the params calculation and return 0 or text size
        $this->assertSame($expectedSize, $actualSize, 'getBodySize must calculate from params when params exist');
        $this->assertNotSame(0, $actualSize, 'getBodySize must not return 0 when params exist');
    }

    public function testGetBodySizeWithoutParamsDoesNotError(): void
    {
        // This test kills IfNegation by verifying no error occurs when hasParams is false
        // If the condition is negated, foreach would be called on null params, causing an error
        $postData = new PostData();

        // Set up error handler to catch any warnings
        $warningTriggered = false;
        $previousHandler = set_error_handler(function ($errno, $errstr) use (&$warningTriggered) {
            if (str_contains($errstr, 'foreach') || str_contains($errstr, 'null')) {
                $warningTriggered = true;
            }

            return false;
        });

        try {
            // This should NOT trigger a foreach warning
            $result = $postData->getBodySize();

            $this->assertFalse($warningTriggered, 'getBodySize() should not attempt foreach on null params');
            $this->assertSame(0, $result, 'getBodySize() should return 0 when no params and no text');
        } finally {
            restore_error_handler();
        }
    }

    public function testGetBodySizeWithTextButNoParams(): void
    {
        // This tests that when we have text but no params, we get text size
        // If IfNegation is applied, it would try foreach on null (error) or skip to wrong branch
        $postData = new PostData();
        $postData->setText('hello world');

        $this->assertFalse($postData->hasParams());
        $this->assertTrue($postData->hasText());

        // Should return text size, not 0 and not cause an error
        $expectedSize = \strlen('hello world');
        $this->assertSame($expectedSize, $postData->getBodySize());
    }

    public function testSetTextIsPublic(): void
    {
        // This test kills the PublicVisibility mutant by explicitly verifying
        // that setText() is publicly accessible from outside the class
        $postData = new PostData();

        // Calling setText from outside the class - this would fail if visibility is protected
        $result = $postData->setText('test content');

        // Verify it returns the PostData instance for method chaining
        $this->assertSame($postData, $result);

        // Verify the text was set
        $this->assertEquals('test content', $postData->getText());

        // This test specifically kills the PublicVisibility mutant at TextTrait.php:20
        // If `public function setText` is changed to `protected function setText`,
        // this test will fail with a fatal error because protected methods
        // cannot be called from outside the class
    }

    public function testGetParamsCallsTraitSetText(): void
    {
        // This test verifies that getParams() calls traitSetText() to ensure
        // params and text are properly synchronized even when set via deserialization
        $serializer = $this->getSerializer();

        // Create JSON with both params and text (shouldn't happen, but could in malformed data)
        // In practice, params are set via deserialization, bypassing setParams()
        $json = json_encode([
            'params' => [
                ['name' => 'key', 'value' => 'value'],
            ],
            'text' => 'some text that should be cleared',
        ]);

        // Deserialize - this sets params directly without calling setParams()
        /** @var PostData $postData */
        $postData = $serializer->deserialize($json, PostData::class, 'json');

        // Before calling getParams(), text might still be set from deserialization
        // (though in this specific case it's cleared by setParams during deserialization)
        // The key point is that getParams() must ensure text is cleared

        // Call getParams() which should call traitSetText() to clear text
        $params = $postData->getParams();

        // Verify we got the params back
        $this->assertCount(1, $params);
        $this->assertEquals('key', $params[0]->getName());

        // Verify text is null (synchronized properly by traitSetText())
        $this->assertFalse($postData->hasText());
        $this->assertNull($postData->getText());

        // This test kills the MethodCallRemoval mutant at PostData.php:37
        // If traitSetText() is not called, text wouldn't be properly cleared
    }
}
