<?php

declare(strict_types=1);

namespace Deviantintegral\Har\Tests\Unit;

use Deviantintegral\Har\Handler\TruncatingDateTimeHandler;
use Deviantintegral\Har\Har;
use Deviantintegral\Har\Log;
use Deviantintegral\Har\Serializer;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * @covers \Deviantintegral\Har\Har
 */
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

    public function testGetSetLog()
    {
        $log = new Log();
        $har = (new Har())->setLog($log);
        $this->assertSame($log, $har->getLog());
    }

    public function testSplitLogEntries()
    {
        $repository = $this->getHarFileRepository();
        $har = $repository->load('www.softwareishard.com-multiple-entries.har');

        $originalEntryCount = \count($har->getLog()->getEntries());
        $this->assertGreaterThan(1, $originalEntryCount);

        $splitHars = [];
        foreach ($har->splitLogEntries() as $index => $splitHar) {
            $splitHars[$index] = $splitHar;
        }

        $this->assertCount($originalEntryCount, $splitHars);

        // Verify each split HAR has only one entry
        foreach ($splitHars as $splitHar) {
            $this->assertCount(1, $splitHar->getLog()->getEntries());
            // Verify it's a different instance (cloned)
            $this->assertNotSame($har, $splitHar);
        }
    }

    public function testCloneIsDeep()
    {
        $repository = $this->getHarFileRepository();
        $har = $repository->load('www.softwareishard.com-multiple-entries.har');

        $originalEntryCount = \count($har->getLog()->getEntries());
        $this->assertGreaterThan(1, $originalEntryCount);

        // Clone the HAR
        $cloned = clone $har;

        // Verify the clone has a different Log instance
        $this->assertNotSame($har->getLog(), $cloned->getLog());

        // Modify the cloned HAR's entries
        $cloned->getLog()->setEntries([]);

        // Verify the original HAR's entries are unchanged
        $this->assertCount($originalEntryCount, $har->getLog()->getEntries());
        $this->assertCount(0, $cloned->getLog()->getEntries());
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
