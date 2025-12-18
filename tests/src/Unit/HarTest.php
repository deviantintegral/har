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
    public function testExportedFixture(string $id, Har $har): void
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

    /**
     * @return \Generator<int, array{0: string, 1: Har}>
     */
    public static function fixtureDataProvider(): \Generator
    {
        $repository = new \Deviantintegral\Har\Repository\HarFileRepository(__DIR__.'/../../fixtures');

        foreach ($repository->loadMultiple() as $id => $har) {
            yield [$id, $har];
        }
    }

    public function testGetSetLog(): void
    {
        $log = new Log();
        $har = (new Har())->setLog($log);
        $this->assertSame($log, $har->getLog());
    }

    public function testSplitLogEntries(): void
    {
        $repository = $this->getHarFileRepository();
        $har = $repository->load('www.softwareishard.com-multiple-entries.har');

        $originalEntryCount = \count($har->getLog()->getEntries());

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

    public function testCloneIsDeep(): void
    {
        $repository = $this->getHarFileRepository();
        $har = $repository->load('www.softwareishard.com-multiple-entries.har');

        $originalEntryCount = \count($har->getLog()->getEntries());

        // Clone the HAR
        $cloned = clone $har;

        // Verify the clone has a different Log instance
        $this->assertNotSame($har->getLog(), $cloned->getLog());

        // Modify the cloned HAR's entries
        $cloned->getLog()->setEntries([]);

        // Verify the original HAR's entries are unchanged
        $this->assertCount($originalEntryCount, $har->getLog()->getEntries());
    }

    public function testCloneBrowserIsDeep(): void
    {
        $repository = $this->getHarFileRepository();
        $har = $repository->load('www.softwareishard.com-multiple-entries.har');

        // Set up a browser object for testing (HAR files may not include browser data)
        $browser = new \Deviantintegral\Har\Browser();
        $browser->setVersion('1.0.0');
        $har->getLog()->setBrowser($browser);

        $originalBrowserVersion = $har->getLog()->getBrowser()->getVersion();

        // Clone the HAR
        $cloned = clone $har;

        // Verify the Browser object is a different instance
        $this->assertNotSame($har->getLog()->getBrowser(), $cloned->getLog()->getBrowser());

        // Modify the cloned HAR's browser version
        $cloned->getLog()->getBrowser()->setVersion('modified-version');

        // Verify the original HAR's browser version is unchanged
        $this->assertSame($originalBrowserVersion, $har->getLog()->getBrowser()->getVersion());
    }

    public function testSplitLogEntriesYieldsWithCorrectIndices(): void
    {
        // This test kills the YieldValue mutant by explicitly verifying
        // that yield uses the index as the key: yield $index => $cloned
        $repository = $this->getHarFileRepository();
        $har = $repository->load('www.softwareishard.com-multiple-entries.har');

        $generator = $har->splitLogEntries();

        // Explicitly verify that each yielded value has the correct key
        $count = 0;
        foreach ($generator as $key => $splitHar) {
            // The key MUST equal the count (0, 1, 2, ...)
            $this->assertSame($count, $key, "Generator key should be {$count} but got {$key}");
            $this->assertInstanceOf(Har::class, $splitHar);
            ++$count;
        }

        // This test specifically kills the YieldValue mutant:
        // If yield $index => $cloned is changed to yield $cloned,
        // the keys would be auto-generated (0, 1, 2, ...) which would still pass
        // BUT the explicit assertion on $key === $count ensures the index is yielded
    }

    /**
     * @param array<mixed> $a
     */
    private function removeCustomFields(array &$a): void
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

    /**
     * @param array<mixed> $a
     */
    private function normalizeDateTime(array &$a): void
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
