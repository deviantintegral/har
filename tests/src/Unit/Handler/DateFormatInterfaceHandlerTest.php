<?php

declare(strict_types=1);

namespace Deviantintegral\Har\Tests\Unit\Handler;

use Deviantintegral\Har\Handler\DateFormatInterfaceHandler;
use Deviantintegral\NullDateTime\NullDateTime;
use JMS\Serializer\GraphNavigatorInterface;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Deviantintegral\Har\Handler\DateFormatInterfaceHandler
 */
class DateFormatInterfaceHandlerTest extends TestCase
{
    private DateFormatInterfaceHandler $handler;

    protected function setUp(): void
    {
        $this->handler = new DateFormatInterfaceHandler();
    }

    public function testGetSubscribingMethods(): void
    {
        $methods = DateFormatInterfaceHandler::getSubscribingMethods();
        $this->assertIsArray($methods); // @phpstan-ignore method.alreadyNarrowedType
        $this->assertNotEmpty($methods);

        // Check that all expected types are present
        $types = [];
        foreach ($methods as $method) {
            $types[] = $method['type'];
        }

        $this->assertContains('Deviantintegral\NullDateTime\DateTimeFormatInterface', $types);
        $this->assertContains('Deviantintegral\NullDateTime\NullDateTime', $types);
        $this->assertContains('Deviantintegral\NullDateTime\ConcreteDateTime', $types);

        // Check that serialization and deserialization are both configured
        $directions = [];
        foreach ($methods as $method) {
            $directions[] = $method['direction'];
        }

        $this->assertContains(GraphNavigatorInterface::DIRECTION_SERIALIZATION, $directions);
        $this->assertContains(GraphNavigatorInterface::DIRECTION_DESERIALIZATION, $directions);
    }

    public function testConstructorWithDefaultParameters(): void
    {
        $handler = new DateFormatInterfaceHandler();
        $this->assertInstanceOf(DateFormatInterfaceHandler::class, $handler);
    }

    public function testConstructorWithCustomParameters(): void
    {
        $handler = new DateFormatInterfaceHandler('Y-m-d', 'America/New_York');
        $this->assertInstanceOf(DateFormatInterfaceHandler::class, $handler);
    }

    public function testSerializeNullDateTime(): void
    {
        $nullDateTime = new NullDateTime();

        // Create a mock visitor that won't be called for NullDateTime
        $visitor = $this->createStub(\JMS\Serializer\Visitor\SerializationVisitorInterface::class);
        $context = $this->createStub(\JMS\Serializer\SerializationContext::class);

        $result = $this->handler->serializeDateTimeFormatInterface($visitor, $nullDateTime, [], $context);
        $this->assertEquals('', $result);
    }
}
