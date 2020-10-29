<?php

declare(strict_types=1);

namespace Deviantintegral\Har;

use Deviantintegral\Har\Handler\DateFormatInterfaceHandler;
use Deviantintegral\JmsSerializerUriHandler\UriHandler;
use Doctrine\Common\Annotations\AnnotationRegistry;
use JMS\Serializer\Handler\HandlerRegistryInterface;
use JMS\Serializer\Naming\IdenticalPropertyNamingStrategy;
use JMS\Serializer\SerializerBuilder;

final class Serializer
{
    public function getSerializer()
    {
        $builder = $this->getSerializerBuilder();

        return $builder->build();
    }

    public function getSerializerBuilder(): SerializerBuilder
    {
        AnnotationRegistry::registerLoader('class_exists');
        $builder = SerializerBuilder::create()
          ->setPropertyNamingStrategy(new IdenticalPropertyNamingStrategy())
          ->configureHandlers(
            function (HandlerRegistryInterface $registry) {
                $registry->registerSubscribingHandler(
                  new DateFormatInterfaceHandler(Log::ISO_8601_MICROSECONDS)
                );
                $registry->registerSubscribingHandler(
                  new \JMS\Serializer\Handler\DateHandler(Log::ISO_8601_MICROSECONDS)
                );
                $registry->registerSubscribingHandler(new UriHandler());
            }
          );

        return $builder;
    }

    public function deserializeHar(string $data): Har
    {
        return $this->getSerializer()->deserialize($data, Har::class, 'json');
    }

    /**
     * @param \Deviantintegral\Har\Har $data
     */
    public function serializeHar(Har $data): string
    {
        return $this->getSerializer()->serialize($data, 'json');
    }
}
