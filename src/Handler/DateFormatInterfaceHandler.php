<?php

declare(strict_types=1);

namespace Deviantintegral\Har\Handler;

use Deviantintegral\NullDateTime\ConcreteDateTime;
use Deviantintegral\NullDateTime\DateTimeFormatInterface;
use Deviantintegral\NullDateTime\NullDateTime;
use JMS\Serializer\GraphNavigatorInterface;
use JMS\Serializer\Handler\SubscribingHandlerInterface;
use JMS\Serializer\JsonDeserializationVisitor;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\Visitor\SerializationVisitorInterface;

class DateFormatInterfaceHandler implements SubscribingHandlerInterface
{
    private \JMS\Serializer\Handler\DateHandler $innerHandler;

    public static function getSubscribingMethods()
    {
        $types = [
            'Deviantintegral\NullDateTime\DateTimeFormatInterface',
            'Deviantintegral\NullDateTime\NullDateTime',
            'Deviantintegral\NullDateTime\ConcreteDateTime',
        ];
        $methods = [];

        foreach ($types as $type) {
            $methods[] = [
                'type' => $type,
                'direction' => GraphNavigatorInterface::DIRECTION_DESERIALIZATION,
                'format' => 'json',
            ];
            $methods[] = [
                'type' => $type,
                'format' => 'json',
                'direction' => GraphNavigatorInterface::DIRECTION_SERIALIZATION,
                'method' => 'serializeDateTimeFormatInterface',
            ];
        }

        return $methods;
    }

    public function __construct(string $defaultFormat = \DateTime::ATOM, string $defaultTimezone = 'UTC')
    {
        $this->innerHandler = new \JMS\Serializer\Handler\DateHandler($defaultFormat, $defaultTimezone);
    }

    public function serializeDateTimeFormatInterface(SerializationVisitorInterface $visitor, DateTimeFormatInterface $date, array $type, SerializationContext $context)
    {
        if ($date instanceof ConcreteDateTime) {
            return $this->innerHandler->serializeDateTime($visitor, $date->getDateTime(), $type, $context);
        }

        return $date->format('');
    }

    public function deserializeDateTimeFormatInterfaceFromJson(JsonDeserializationVisitor $visitor, $data, array $type): DateTimeFormatInterface
    {
        if (null === $data || '' === $data) {
            return new NullDateTime();
        }

        /** @var \DateTime $dateTime */
        $dateTime = $this->innerHandler->deserializeDateTimeFromJson(
            $visitor,
            $data,
            $type
        );

        return new ConcreteDateTime($dateTime);
    }
}
