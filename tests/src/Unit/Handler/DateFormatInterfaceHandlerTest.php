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

    public function testSerializeNullDateTime(): void
    {
        $nullDateTime = new NullDateTime();

        // Create a mock visitor that won't be called for NullDateTime
        $visitor = $this->createStub(\JMS\Serializer\Visitor\SerializationVisitorInterface::class);
        $context = $this->createStub(\JMS\Serializer\SerializationContext::class);

        $result = $this->handler->serializeDateTimeFormatInterface($visitor, $nullDateTime, [], $context);
        $this->assertEquals('', $result);
    }

    public function testDeserializeNullOrEmptyStringReturnsNullDateTime(): void
    {
        // Test that the handler's condition (null === $data || '' === $data) works correctly
        // We test with empty string since that's what the handler processes
        $serializer = new \Deviantintegral\Har\Serializer();

        $json = '{"name": "test", "value": "value", "expires": ""}';
        $result = $serializer->getSerializer()->deserialize($json, \Deviantintegral\Har\Cookie::class, 'json');

        $this->assertInstanceOf(\Deviantintegral\Har\Cookie::class, $result);
        // Empty string should return NullDateTime via the handler
        $this->assertInstanceOf(NullDateTime::class, $result->getExpires());
    }

    public function testDeserializeValidDateTimeReturnsConcreteDateTime(): void
    {
        // Test using the actual serializer to deserialize a Cookie with valid expires datetime
        $serializer = new \Deviantintegral\Har\Serializer();

        $json = '{"name": "test", "value": "value", "expires": "2023-01-15T10:30:00.000Z"}';
        $result = $serializer->getSerializer()->deserialize($json, \Deviantintegral\Har\Cookie::class, 'json');

        $this->assertInstanceOf(\Deviantintegral\Har\Cookie::class, $result);
        $this->assertInstanceOf(\Deviantintegral\NullDateTime\ConcreteDateTime::class, $result->getExpires());
    }
}
