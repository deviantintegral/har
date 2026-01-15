<?php

declare(strict_types=1);

namespace Deviantintegral\Har\Tests\Unit\Validation;

use Deviantintegral\Har\Validation\ValidationError;
use Deviantintegral\Har\Validation\ValidationResult;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ValidationResult::class)]
class ValidationResultTest extends TestCase
{
    public function testIsValidWithNoErrors(): void
    {
        $result = new ValidationResult([]);

        $this->assertTrue($result->isValid());
    }

    public function testIsValidWithErrors(): void
    {
        $result = new ValidationResult([
            new ValidationError('Test error'),
        ]);

        $this->assertFalse($result->isValid());
    }

    public function testGetErrors(): void
    {
        $error = new ValidationError('Test error');
        $result = new ValidationResult([$error]);

        $this->assertCount(1, $result->getErrors());
        $this->assertSame($error, $result->getErrors()[0]);
    }

    public function testGetErrorCount(): void
    {
        $result = new ValidationResult([
            new ValidationError('Error 1'),
            new ValidationError('Error 2'),
            new ValidationError('Error 3'),
        ]);

        $this->assertSame(3, $result->getErrorCount());
    }

    public function testValidFactory(): void
    {
        $result = ValidationResult::valid();

        $this->assertTrue($result->isValid());
        $this->assertSame(0, $result->getErrorCount());
    }

    public function testInvalidFactory(): void
    {
        $errors = [
            new ValidationError('Error 1'),
            new ValidationError('Error 2'),
        ];
        $result = ValidationResult::invalid($errors);

        $this->assertFalse($result->isValid());
        $this->assertSame(2, $result->getErrorCount());
    }

    public function testMerge(): void
    {
        $result1 = new ValidationResult([
            new ValidationError('Error 1'),
        ]);
        $result2 = new ValidationResult([
            new ValidationError('Error 2'),
            new ValidationError('Error 3'),
        ]);
        $result3 = ValidationResult::valid();

        $merged = ValidationResult::merge($result1, $result2, $result3);

        $this->assertFalse($merged->isValid());
        $this->assertSame(3, $merged->getErrorCount());
    }

    public function testMergeAllValid(): void
    {
        $result1 = ValidationResult::valid();
        $result2 = ValidationResult::valid();

        $merged = ValidationResult::merge($result1, $result2);

        $this->assertTrue($merged->isValid());
    }
}
