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
        $this->expectExceptionMessage('does not exist');
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

    public function testGetIdsLengthCheckBoundary(): void
    {
        $tempDir = sys_get_temp_dir().'/har_test_'.uniqid();
        mkdir($tempDir);

        // Test the strlen < 4 boundary condition
        // Note: The shortest possible .har file is "?.har" (5 chars)
        // since we need at least 1 char before ".har" (4 chars)
        // The length check filters ., .., and 3-char names
        $files = [
            'abc' => '',        // 3 chars - should be filtered by length check
            'abcd' => '',       // 4 chars - should pass length check (but fail extension)
            '1.har' => '{}',    // 5 chars - should pass both checks
        ];

        foreach ($files as $filename => $content) {
            file_put_contents($tempDir.'/'.$filename, $content);
        }

        $repository = new HarFileRepository($tempDir);
        $ids = $repository->getIds();

        // Only 1.har (5 chars) should pass both length and extension checks
        $this->assertContains('1.har', $ids);

        // The 3-char file should be filtered by length check
        $this->assertNotContains('abc', $ids);

        // The 4-char file should pass length check but fail extension check
        // This tests that < 4 is correct (not <= 4 which would filter it)
        $this->assertNotContains('abcd', $ids);

        // Verify 1.har is the only result (proves 3-char was filtered, 4-char passed length check)
        $this->assertCount(1, $ids);

        // Clean up
        foreach (array_keys($files) as $filename) {
            unlink($tempDir.'/'.$filename);
        }
        rmdir($tempDir);
    }

    public function testGetIdsLessThanFourCharactersFiltered(): void
    {
        // This test explicitly kills the LessThan mutant by testing
        // that strlen < 4 correctly filters 3-char files but not 4-char files
        $tempDir = sys_get_temp_dir().'/har_test_'.uniqid();
        mkdir($tempDir);

        $files = [
            'a.b' => '{}',      // 3 chars - MUST be filtered (strlen < 4 is true)
            'ab.c' => '{}',     // 4 chars - must NOT be filtered by length check (strlen < 4 is false)
            '1.har' => '{}',    // 5 chars with .har extension - should pass
        ];

        foreach ($files as $filename => $content) {
            file_put_contents($tempDir.'/'.$filename, $content);
        }

        $repository = new HarFileRepository($tempDir);
        $ids = $repository->getIds();

        // 3-char file must be filtered
        $this->assertNotContains('a.b', $ids, '3-char files must be filtered by strlen < 4');

        // 4-char file should pass the length check (even though it lacks .har extension)
        // Since it lacks .har, it will be filtered by the extension check, not length
        $this->assertNotContains('ab.c', $ids, '4-char file filtered by extension, not length');

        // 5-char .har file should pass both checks
        $this->assertContains('1.har', $ids);

        // This test specifically kills the LessThan mutant at HarFileRepository.php:58
        // If strlen($har_file) < 4 is changed to strlen($har_file) <= 4,
        // then 4-char files would incorrectly be filtered by the length check
        // The boundary is crucial: < 4 means "filter 0-3", <= 4 means "filter 0-4"

        // Clean up
        foreach (array_keys($files) as $filename) {
            unlink($tempDir.'/'.$filename);
        }
        rmdir($tempDir);
    }
}
