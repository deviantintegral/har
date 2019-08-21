<?php

declare(strict_types=1);

namespace Deviantintegral\Har;

use Deviantintegral\JmsSerializerUriHandler\UriHandler;
use Doctrine\Common\Annotations\AnnotationRegistry;
use JMS\Serializer\Handler\DateHandler;
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

    /**
     * @return \JMS\Serializer\SerializerBuilder
     */
    public function getSerializerBuilder(): \JMS\Serializer\SerializerBuilder
    {
        AnnotationRegistry::registerLoader('class_exists');
        $builder = SerializerBuilder::create()
          ->setPropertyNamingStrategy(new IdenticalPropertyNamingStrategy())
          ->configureHandlers(
            function (HandlerRegistryInterface $registry) {
                $registry->registerSubscribingHandler(
                  new DateHandler(Log::ISO_8601_MICROSECONDS)
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
}
