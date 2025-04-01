<?php

declare(strict_types=1);

namespace Deviantintegral\Har;

use Deviantintegral\Har\Handler\DateFormatInterfaceHandler;
use Deviantintegral\Har\Handler\TruncatingDateTimeHandler;
use Deviantintegral\JmsSerializerUriHandler\UriHandler;
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
        $builder = SerializerBuilder::create()
          ->setPropertyNamingStrategy(new IdenticalPropertyNamingStrategy())
          ->configureHandlers(
              function (HandlerRegistryInterface $registry) {
                  $registry->registerSubscribingHandler(
                      new DateFormatInterfaceHandler(Log::ISO_8601_MICROSECONDS)
                  );
                  $registry->registerSubscribingHandler(
                      new TruncatingDateTimeHandler(Log::ISO_8601_MICROSECONDS)
                  );
                  $registry->registerSubscribingHandler(new UriHandler());
              }
          );

        return $builder;
    }

    public function deserializeHar(string $data): Har
    {
        $data = $this->removeBOM($data);

        return $this->getSerializer()->deserialize($data, Har::class, 'json');
    }

    public function serializeHar(Har $data): string
    {
        return $this->getSerializer()->serialize($data, 'json');
    }

    /**
     * Remove a leading byte order mark.
     *
     * Some text editors and tools (notably Fiddler on Windows) emit a byte
     * order mark even on UTF-8 files where it's not required. However, the
     * spec still allows it to exist, and PHP's json_decode() chooses to not
     * handle it. See https://tools.ietf.org/html/rfc7159
     * 8.1. Character Encoding where it states that implementations must
     * not emit a BOM, by may optionally decide to ignore it on parsing.
     */
    public function removeBOM(string $data): string
    {
        if (substr($data, 0, 3) == pack('CCC', 0xEF, 0xBB, 0xBF)) {
            $data = substr($data, 3);
        }

        return $data;
    }
}
