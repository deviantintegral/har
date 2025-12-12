<?php

declare(strict_types=1);

namespace Deviantintegral\Har\Tests\Unit;

use Deviantintegral\Har\Handler\TruncatingDateTimeHandler;
use Deviantintegral\Har\Har;
use Deviantintegral\Har\Log;
use Deviantintegral\Har\Serializer;
use PHPUnit\Framework\Attributes\DataProvider;

class HarTest extends HarTestBase
{
    /**
     * Tests deserializing and reserializing a complete HAR file.
     */
    #[DataProvider('fixtureDataProvider')]
    public function testExportedFixture(string $id, Har $har)
    {
        $repository = $this->getHarFileRepository();
        $file = $repository->loadJson($id);
        $file = (new Serializer())->removeBOM($file);
        $jsonDecode = json_decode($file, true);
        $this->removeCustomFields($jsonDecode);
        $this->normalizeDateTime($jsonDecode);
        $serialized = $this->getSerializer()->serialize($har, 'json');
        $this->assertEquals($jsonDecode, json_decode($serialized, true));
    }

    public static function fixtureDataProvider()
    {
        $repository = new \Deviantintegral\Har\Repository\HarFileRepository(__DIR__.'/../../fixtures');

        foreach ($repository->loadMultiple() as $id => $har) {
            yield [$id, $har];
        }
    }

    private function removeCustomFields(array &$a)
    {
        foreach ($a as &$value) {
            if (\is_array($value)) {
                $this->removeCustomFields($value);
            }
        }

        $a = array_filter($a, function ($key): bool {
            return !\is_string($key) || !str_starts_with($key, '_');
        }, \ARRAY_FILTER_USE_KEY);
    }

    private function normalizeDateTime(array &$a)
    {
        foreach ($a as &$value) {
            if (\is_array($value)) {
                $this->normalizeDateTime($value);
            }
        }

        $keys = ['startedDateTime', 'expires'];
        $handler = new TruncatingDateTimeHandler();
        foreach ($a as $key => &$value) {
            if (\in_array($key, $keys, true) && !empty($value)) {
                $value = $handler->truncateMicroseconds($value);
                $date = \DateTime::createFromFormat(Log::ISO_8601_MICROSECONDS, $value);
                $value = $date->format(Log::ISO_8601_MICROSECONDS);
            }
        }
    }
}
