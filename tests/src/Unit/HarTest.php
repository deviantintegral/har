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

    public function testSplitLogEntries(): void
    {
        $repository = $this->getHarFileRepository();
        $har = $repository->load('www.softwareishard.com-multiple-entries.har');

        $splitHars = [];
        foreach ($har->splitLogEntries() as $index => $splitHar) {
            $splitHars[$index] = $splitHar;
        }

        // Verify it's a different instance (cloned)
        foreach ($splitHars as $splitHar) {
            $this->assertNotSame($har, $splitHar);
        }
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
