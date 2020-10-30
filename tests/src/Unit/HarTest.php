<?php

declare(strict_types=1);

namespace Deviantintegral\Har\Tests\Unit;

use Deviantintegral\Har\Log;
use Deviantintegral\Har\Serializer;

class HarTest extends HarTestBase
{
    /**
     * Tests deserializing and reserializing a complete HAR file.
     */
    public function testExportedFixture()
    {
        $repository = $this->getHarFileRepository();

        foreach ($repository->loadMultiple() as $id => $har) {
            $file = $repository->loadJson($id);
            $file = (new Serializer())->removeBOM($file);
            $jsonDecode = json_decode($file, true);
            $this->removeCustomFields($jsonDecode);
            $this->normalizeDateTime($jsonDecode);
            $serialized = $this->getSerializer()->serialize($har, 'json');
            $this->assertEquals($jsonDecode, json_decode($serialized, true));
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
            return !\is_string($key) || !(0 === strpos($key, '_'));
        }, ARRAY_FILTER_USE_KEY);
    }

    private function normalizeDateTime(array &$a)
    {
        foreach ($a as &$value) {
            if (\is_array($value)) {
                $this->normalizeDateTime($value);
            }
        }

        $keys = ['startedDateTime', 'expires'];
        foreach ($a as $key => &$value) {
            if (\in_array($key, $keys, true) && !empty($value)) {
                $date = \DateTime::createFromFormat(Log::ISO_8601_MICROSECONDS, $value);
                $value = $date->format(Log::ISO_8601_MICROSECONDS);
            }
        }
    }
}
