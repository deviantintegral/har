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

        // Verify method names are correctly formed for DateTimeInterface
        $dateTimeInterfaceMethods = array_filter($methods, fn ($m) => 'DateTimeInterface' === $m['type']);
        $methodNames = array_column($dateTimeInterfaceMethods, 'method');

        // Method names should be deserializeDateTimeFromJson and deserializeDateTimeFromXml
        $this->assertContains('deserializeDateTimeFromJson', $methodNames);
        $this->assertContains('deserializeDateTimeFromXml', $methodNames);
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
