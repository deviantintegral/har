<?php

declare(strict_types=1);

namespace Deviantintegral\Har\Handler;

use JMS\Serializer\GraphNavigatorInterface;
use JMS\Serializer\Handler\DateHandler;
use JMS\Serializer\Handler\SubscribingHandlerInterface;
use JMS\Serializer\JsonDeserializationVisitor;

/**
 * Truncates ISO timestamp microseconds to 6 digits.
 */
class TruncatingDateTimeHandler implements SubscribingHandlerInterface
{
    /**
     * @var DateHandler
     */
    private $innerHandler;

    public static function getSubscribingMethods()
    {
        $methods = [];
        $types = ['DateTime', 'DateTimeImmutable', 'DateInterval'];

        foreach (['json', 'xml'] as $format) {
            foreach ($types as $type) {
                $methods[] = [
                    'type' => $type,
                    'direction' => GraphNavigatorInterface::DIRECTION_DESERIALIZATION,
                    'format' => $format,
                ];
                $methods[] = [
                    'type' => $type,
                    'format' => $format,
                    'direction' => GraphNavigatorInterface::DIRECTION_SERIALIZATION,
                    'method' => 'serialize'.$type,
                ];
            }

            $methods[] = [
                'type' => 'DateTimeInterface',
                'direction' => GraphNavigatorInterface::DIRECTION_DESERIALIZATION,
                'format' => $format,
                'method' => 'deserializeDateTimeFrom'.ucfirst($format),
            ];
        }

        return $methods;
    }

    public function __construct(string $defaultFormat = \DateTime::ATOM, string $defaultTimezone = 'UTC')
    {
        $this->innerHandler = new DateHandler($defaultFormat, $defaultTimezone);
    }

    public function deserializeDateTimeFromJson(JsonDeserializationVisitor $visitor, $data, array $type): ?\DateTimeInterface
    {
        $data = $this->truncateMicroseconds($data);

        /* @var \DateTime $dateTime */
        return $this->innerHandler->deserializeDateTimeFromJson(
            $visitor,
            $data,
            $type
        );
    }

    /**
     * Delegate undefined methods to the inner class.
     */
    public function __call(string $name, array $arguments)
    {
        return $this->innerHandler->{$name}(...$arguments);
    }

    /**
     * Truncate microseconds in a date to 6 digits of precision, which is the maximum PHP's DateTime supports.
     *
     * @return string|string[]
     */
    public function truncateMicroseconds($data)
    {
        $microseconds = substr($data, 19);
        if (str_contains($data, '+')) {
            $microseconds = strstr($microseconds, '+', true);
        }
        if (str_contains($data, 'Z')) {
            $microseconds = strstr($microseconds, 'Z', true);
        }
        if (str_contains($data, 'UTC')) {
            $microseconds = strstr($microseconds, 'UTC', true);
        }
        $truncated = substr($microseconds, 0, 7);
        $data = str_replace($microseconds, $truncated, $data);

        return $data;
    }
}
