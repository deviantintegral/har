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

    public function testGetIdsFiltersFilesWithLessThanFourCharacters(): void
    {
        $tempDir = sys_get_temp_dir().'/har_test_'.uniqid();
        mkdir($tempDir);

        // Create files with various lengths
        $files = [
            'a' => '',           // 1 character - should be filtered
            'ab' => '',          // 2 characters - should be filtered
            'abc' => '',         // 3 characters - should be filtered
            'test' => '',        // 4 characters but no .har extension - should be filtered
            'a.bc' => '',        // 4 characters but no .har extension - should be filtered
            'a.har' => '{}',     // 5 characters with .har extension - should be included
            'ab.har' => '{}',    // 6 characters with .har extension - should be included
        ];

        foreach ($files as $filename => $content) {
            file_put_contents($tempDir.'/'.$filename, $content);
        }

        $repository = new HarFileRepository($tempDir);
        $ids = $repository->getIds();

        // Only files with .har extension and length >= 4 should be included
        $this->assertContains('a.har', $ids);
        $this->assertContains('ab.har', $ids);

        // All other files should be filtered out
        $this->assertNotContains('a', $ids);
        $this->assertNotContains('ab', $ids);
        $this->assertNotContains('abc', $ids);
        $this->assertNotContains('test', $ids);
        $this->assertNotContains('a.bc', $ids);

        // Clean up
        foreach (array_keys($files) as $filename) {
            unlink($tempDir.'/'.$filename);
        }
        rmdir($tempDir);
    }
}
