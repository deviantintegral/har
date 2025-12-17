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
    public function testSetTextIsPublic(): void
    {
        // This test kills the PublicVisibility mutation in TextTrait::setText
        // by using a class that uses the trait WITHOUT aliasing/overriding.
        //
        // Unlike PostData and Content which alias setText as traitSetText
        // and provide their own public setText, TextTraitTestClass uses
        // the trait's method directly. If the trait's method becomes
        // protected, this test will fail with a fatal error.
        $instance = new TextTraitTestClass();

        // Calling setText from outside the class - would fail if protected
        $result = $instance->setText('test content');

        // Verify method chaining works (returns self)
        $this->assertSame($instance, $result);

        // Verify the text was set
        $this->assertTrue($instance->hasText());
        $this->assertEquals('test content', $instance->getText());
    }

    public function testSetTextWithNull(): void
    {
        $instance = new TextTraitTestClass();
        $instance->setText('initial');
        $this->assertTrue($instance->hasText());

        // Setting null should clear the text
        $instance->setText(null);
        $this->assertFalse($instance->hasText());
        $this->assertNull($instance->getText());
    }

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
