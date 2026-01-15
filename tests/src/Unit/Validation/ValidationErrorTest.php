<?php

declare(strict_types=1);

namespace Deviantintegral\Har\Tests\Unit\Validation;

use Deviantintegral\Har\Validation\ValidationError;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ValidationError::class)]
class ValidationErrorTest extends TestCase
{
    public function testGetMessage(): void
    {
        $error = new ValidationError('Test error message');

        $this->assertSame('Test error message', $error->getMessage());
    }

    public function testGetPath(): void
    {
        $error = new ValidationError('Test error', 'log.entries[0].request');

        $this->assertSame('log.entries[0].request', $error->getPath());
    }

    public function testGetPathDefaultsToEmptyString(): void
    {
        $error = new ValidationError('Test error');

        $this->assertSame('', $error->getPath());
    }

    public function testGetProperty(): void
    {
        $error = new ValidationError('Test error', 'log.entries[0]', 'request');

        $this->assertSame('request', $error->getProperty());
    }

    public function testGetPropertyDefaultsToNull(): void
    {
        $error = new ValidationError('Test error');

        $this->assertNull($error->getProperty());
    }

    public function testGetFullMessageWithPath(): void
    {
        $error = new ValidationError('Test error', 'log.entries[0].request');

        $this->assertSame('[log.entries[0].request] Test error', $error->getFullMessage());
    }

    public function testGetFullMessageWithoutPath(): void
    {
        $error = new ValidationError('Test error');

        $this->assertSame('Test error', $error->getFullMessage());
    }
}
