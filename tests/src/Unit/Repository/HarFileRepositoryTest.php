<?php

declare(strict_types=1);

namespace Deviantintegral\Har\Tests\Unit\Repository;

use Deviantintegral\Har\Har;
use Deviantintegral\Har\Repository\HarFileRepository;
use Deviantintegral\Har\Tests\Unit\HarTestBase;

/**
 * @covers \Deviantintegral\Har\Repository\HarFileRepository
 */
class HarFileRepositoryTest extends HarTestBase
{
    private HarFileRepository $repository;

    protected function setUp(): void
    {
        $this->repository = $this->getHarFileRepository();
    }

    public function testLoad(): void
    {
        $har = $this->repository->load('www.softwareishard.com-single-entry.har');
        $this->assertInstanceOf(Har::class, $har);
    }

    public function testLoadJson(): void
    {
        $json = $this->repository->loadJson('www.softwareishard.com-single-entry.har');
        $this->assertNotEmpty($json);
        $decoded = json_decode($json, true);
        $this->assertIsArray($decoded);
        $this->assertArrayHasKey('log', $decoded);
    }

    public function testGetIds(): void
    {
        $ids = $this->repository->getIds();
        $this->assertNotEmpty($ids);
        $this->assertContains('www.softwareishard.com-single-entry.har', $ids);
        $this->assertContains('www.softwareishard.com-multiple-entries.har', $ids);
    }

    public function testGetIdsSorted(): void
    {
        $ids = $this->repository->getIds();
        $sorted = $ids;
        sort($sorted, \SORT_NATURAL);
        $this->assertEquals($sorted, $ids);
    }

    public function testLoadMultipleWithIds(): void
    {
        $ids = ['www.softwareishard.com-single-entry.har', 'www.softwareishard.com-multiple-entries.har'];
        $generator = $this->repository->loadMultiple($ids);
        $this->assertInstanceOf(\Generator::class, $generator);

        $hars = iterator_to_array($generator);
        $this->assertCount(2, $hars);
        $this->assertArrayHasKey('www.softwareishard.com-single-entry.har', $hars);
        $this->assertInstanceOf(Har::class, $hars['www.softwareishard.com-single-entry.har']);
    }

    public function testLoadMultipleWithoutIds(): void
    {
        $generator = $this->repository->loadMultiple();
        $this->assertInstanceOf(\Generator::class, $generator);

        $hars = iterator_to_array($generator);
        $this->assertNotEmpty($hars);
        foreach ($hars as $har) {
            $this->assertInstanceOf(Har::class, $har);
        }
    }

    public function testLoadJsonThrowsExceptionForInvalidFile(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->repository->loadJson('non-existent-file.har');
    }

    public function testGetIdsReturnsEmptyArrayForNonExistentDirectory(): void
    {
        $repository = new HarFileRepository('/path/to/non-existent-directory');
        $ids = $repository->getIds();
        $this->assertEmpty($ids);
    }

    public function testLoadJsonHandlesEmptyFile(): void
    {
        $tempDir = sys_get_temp_dir().'/har_test_'.uniqid();
        mkdir($tempDir);
        $emptyFile = $tempDir.'/empty.har';
        file_put_contents($emptyFile, '');

        $repository = new HarFileRepository($tempDir);
        $this->expectException(\RuntimeException::class);
        try {
            $repository->loadJson('empty.har');
        } catch (\RuntimeException $e) {
            unlink($emptyFile);
            rmdir($tempDir);
            throw $e;
        }
    }
}
