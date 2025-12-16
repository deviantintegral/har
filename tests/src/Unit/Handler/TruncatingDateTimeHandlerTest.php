<?php

declare(strict_types=1);

namespace Deviantintegral\Har\Tests\Unit\Handler;

use Deviantintegral\Har\Handler\TruncatingDateTimeHandler;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Deviantintegral\Har\Handler\TruncatingDateTimeHandler
 */
class TruncatingDateTimeHandlerTest extends TestCase
{
    private TruncatingDateTimeHandler $handler;

    protected function setUp(): void
    {
        $this->handler = new TruncatingDateTimeHandler();
    }

    public function testGetSubscribingMethods(): void
    {
        $methods = TruncatingDateTimeHandler::getSubscribingMethods();
        $this->assertIsArray($methods); // @phpstan-ignore method.alreadyNarrowedType
        $this->assertNotEmpty($methods);

        // Check that all expected types are present
        $types = [];
        foreach ($methods as $method) {
            $types[] = $method['type'];
        }

        $this->assertContains('DateTime', $types);
        $this->assertContains('DateTimeImmutable', $types);
        $this->assertContains('DateInterval', $types);
        $this->assertContains('DateTimeInterface', $types);

        // Check that both json and xml formats are configured
        $formats = [];
        foreach ($methods as $method) {
            $formats[] = $method['format'];
        }

        $this->assertContains('json', $formats);
        $this->assertContains('xml', $formats);
    }

    public function testConstructorWithDefaultParameters(): void
    {
        $handler = new TruncatingDateTimeHandler();
        $this->assertInstanceOf(TruncatingDateTimeHandler::class, $handler);
    }

    public function testConstructorWithCustomParameters(): void
    {
        $handler = new TruncatingDateTimeHandler('Y-m-d', 'America/New_York');
        $this->assertInstanceOf(TruncatingDateTimeHandler::class, $handler);
    }

    public function testTruncateMicrosecondsWithPlus(): void
    {
        $data = '2024-01-01T12:00:00.123456789+00:00';
        $result = $this->handler->truncateMicroseconds($data);
        $this->assertEquals('2024-01-01T12:00:00.123456+00:00', $result);
    }

    public function testTruncateMicrosecondsWithZ(): void
    {
        $data = '2024-01-01T12:00:00.123456789Z';
        $result = $this->handler->truncateMicroseconds($data);
        $this->assertEquals('2024-01-01T12:00:00.123456Z', $result);
    }

    public function testTruncateMicrosecondsWithUTC(): void
    {
        $data = '2024-01-01T12:00:00.123456789UTC';
        $result = $this->handler->truncateMicroseconds($data);
        $this->assertEquals('2024-01-01T12:00:00.123456UTC', $result);
    }

    public function testTruncateMicrosecondsWithShorterPrecision(): void
    {
        $data = '2024-01-01T12:00:00.123+00:00';
        $result = $this->handler->truncateMicroseconds($data);
        $this->assertEquals('2024-01-01T12:00:00.123+00:00', $result);
    }

    public function testTruncateMicrosecondsWithExactSixDigits(): void
    {
        $data = '2024-01-01T12:00:00.123456+00:00';
        $result = $this->handler->truncateMicroseconds($data);
        $this->assertEquals('2024-01-01T12:00:00.123456+00:00', $result);
    }
}
