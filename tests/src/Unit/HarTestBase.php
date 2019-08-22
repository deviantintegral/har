<?php

declare(strict_types=1);

namespace Deviantintegral\Har\Tests\Unit;

use Deviantintegral\Har\Repository\HarFileRepository;
use Deviantintegral\Har\Serializer;
use PHPUnit\Framework\TestCase;

abstract class HarTestBase extends TestCase
{
    /**
     * @return \JMS\Serializer\SerializerInterface
     */
    protected function getSerializer(): \JMS\Serializer\SerializerInterface
    {
        return (new Serializer())->getSerializer();
    }

    /**
     * @param string $serialized
     * @param string $class
     * @param mixed  $expected
     */
    protected function assertDeserialize(
      string $serialized,
      string $class,
      $expected
    ): void {
        $serializer = $this->getSerializer();
        $deserialized = $serializer->deserialize(
          $serialized,
          $class,
          'json'
        );
        $this->assertEquals($expected, $deserialized);
    }

    /**
     * @return \Deviantintegral\Har\Repository\HarFileRepository
     */
    protected function getHarFileRepository(
    ): \Deviantintegral\Har\Repository\HarFileRepository {
        $repository = new HarFileRepository(__DIR__.'/../../fixtures');

        return $repository;
    }
}
