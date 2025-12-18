<?php

declare(strict_types=1);

namespace Deviantintegral\Har\Tests\Unit\SharedFields;

use Deviantintegral\Har\Tests\Fixtures\TextTraitTestClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Deviantintegral\Har\SharedFields\TextTrait
 */
class TextTraitTest extends TestCase
{
    public function testHasText(): void
    {
        $instance = new TextTraitTestClass();

        // Initially no text
        $this->assertFalse($instance->hasText());

        // After setting text
        $instance->setText('content');
        $this->assertTrue($instance->hasText());

        // After clearing text
        $instance->setText(null);
        $this->assertFalse($instance->hasText());
    }

    public function testGetText(): void
    {
        $instance = new TextTraitTestClass();

        // Initially null
        $this->assertNull($instance->getText());

        // After setting
        $instance->setText('some text');
        $this->assertEquals('some text', $instance->getText());
    }
}
