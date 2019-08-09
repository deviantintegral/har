<?php

declare(strict_types=1);

namespace Deviantintegral\Har\Tests\Unit;

use Deviantintegral\Har\Serializer;
use PHPUnit\Framework\TestCase;

abstract class HarTestBase extends TestCase
{
    /**
     * @return \JMS\Serializer\SerializerInterface
     */
    protected function getSerializer(): \JMS\Serializer\SerializerInterface
    {
        $serializer = new Serializer();

        return $serializer->getSerializer();
    }
}
