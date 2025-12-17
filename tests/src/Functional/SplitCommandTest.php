<?php

declare(strict_types=1);

namespace Deviantintegral\Har\Tests\Functional;

use Deviantintegral\Har\Command\SplitCommand;
use Deviantintegral\Har\Serializer;
use Deviantintegral\Har\Tests\Unit\HarTestBase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class SplitCommandTest extends HarTestBase
{
    private string $tempDir;
    private CommandTester $commandTester;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a temporary directory for test outputs
        $this->tempDir = sys_get_temp_dir().'/har_test_'.uniqid();
        mkdir($this->tempDir, recursive: true);

        // Set up the command tester
        $command = new SplitCommand();
        $this->commandTester = new CommandTester($command);
    }

    protected function tearDown(): void
    {
        // Clean up temporary directory
        if (is_dir($this->tempDir)) {
            $this->recursiveRemoveDirectory($this->tempDir);
        }

        parent::tearDown();
    }

    public function testSplitMultipleEntries(): void
    {
        $harFile = __DIR__.'/../../fixtures/www.softwareishard.com-multiple-entries.har';

        $this->commandTester->execute([
            'har' => $harFile,
            'destination' => $this->tempDir,
        ]);

        // Should succeed
        $this->assertSame(Command::SUCCESS, $this->commandTester->getStatusCode());

        // Should create 11 files (one per entry)
        $files = glob($this->tempDir.'/*.har');
        $this->assertCount(11, $files);

        // Verify file names are sequential
        $expectedFiles = [];
        for ($i = 1; $i <= 11; ++$i) {
            $expectedFiles[] = $this->tempDir.'/'.$i.'.har';
        }
        natsort($files);
        $files = array_values($files); // Re-index array after sorting // @phpstan-ignore argument.templateType
        $this->assertEquals($expectedFiles, $files);

        // Verify each file is valid HAR with single entry
        $serializer = new Serializer();
        foreach ($files as $file) {
            $contents = file_get_contents($file);
            $har = $serializer->deserializeHar($contents);
            $this->assertCount(1, $har->getLog()->getEntries(), 'Each split file should have exactly one entry');
        }
    }

    public function testSplitSingleEntry(): void
    {
        $harFile = __DIR__.'/../../fixtures/www.softwareishard.com-single-entry.har';

        $this->commandTester->execute([
            'har' => $harFile,
            'destination' => $this->tempDir,
        ]);

        $this->assertSame(Command::SUCCESS, $this->commandTester->getStatusCode());

        // Should create 1 file
        $files = glob($this->tempDir.'/*.har');
        $this->assertCount(1, $files);
        $this->assertFileExists($this->tempDir.'/1.har');

        // Verify the file is valid HAR
        $serializer = new Serializer();
        $contents = file_get_contents($this->tempDir.'/1.har');
        $har = $serializer->deserializeHar($contents);
        $this->assertCount(1, $har->getLog()->getEntries());
    }

    public function testSplitWithMd5Option(): void
    {
        $harFile = __DIR__.'/../../fixtures/www.softwareishard.com-multiple-entries.har';

        $this->commandTester->execute([
            'har' => $harFile,
            'destination' => $this->tempDir,
            '--md5' => true,
        ]);

        $this->assertSame(Command::SUCCESS, $this->commandTester->getStatusCode());

        // Should create 11 files
        $files = glob($this->tempDir.'/*.har');
        $this->assertCount(11, $files);

        // Verify file names are MD5 hashes
        foreach ($files as $file) {
            $basename = basename($file, '.har');
            $this->assertMatchesRegularExpression('/^[a-f0-9]{32}$/', $basename, 'Filename should be MD5 hash');
        }

        // Verify each file is valid HAR
        $serializer = new Serializer();
        foreach ($files as $file) {
            $contents = file_get_contents($file);
            $har = $serializer->deserializeHar($contents);
            $this->assertCount(1, $har->getLog()->getEntries());

            // Verify the filename matches the MD5 of the request URL
            $url = (string) $har->getLog()->getEntries()[0]->getRequest()->getUrl();
            $expectedHash = md5($url);
            $basename = basename($file, '.har');
            $this->assertEquals($expectedHash, $basename);
        }
    }

    public function testSplitWithForceOption(): void
    {
        $harFile = __DIR__.'/../../fixtures/www.softwareishard.com-single-entry.har';

        // First split
        $this->commandTester->execute([
            'har' => $harFile,
            'destination' => $this->tempDir,
        ]);
        $this->assertSame(Command::SUCCESS, $this->commandTester->getStatusCode());

        $outputFile = $this->tempDir.'/1.har';
        $this->assertFileExists($outputFile);
        $originalContent = file_get_contents($outputFile);

        // Modify the file to verify it gets overwritten
        file_put_contents($outputFile, 'modified content');
        $this->assertEquals('modified content', file_get_contents($outputFile));

        // Split again with --force
        $this->commandTester->execute([
            'har' => $harFile,
            'destination' => $this->tempDir,
            '--force' => true,
        ]);
        $this->assertSame(Command::SUCCESS, $this->commandTester->getStatusCode());

        // Verify the file was overwritten with original content
        $newContent = file_get_contents($outputFile);
        $this->assertEquals($originalContent, $newContent);
        $this->assertNotEquals('modified content', $newContent);
    }

    public function testSplitFailsWhenFileExistsWithoutForce(): void
    {
        $harFile = __DIR__.'/../../fixtures/www.softwareishard.com-single-entry.har';

        // First split
        $this->commandTester->execute([
            'har' => $harFile,
            'destination' => $this->tempDir,
        ]);
        $this->assertSame(Command::SUCCESS, $this->commandTester->getStatusCode());

        // Try to split again without --force
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/exists\. Use --force to overwrite/');

        $this->commandTester->execute([
            'har' => $harFile,
            'destination' => $this->tempDir,
        ]);
    }

    public function testSplitToCurrentDirectoryByDefault(): void
    {
        $harFile = __DIR__.'/../../fixtures/www.softwareishard.com-single-entry.har';

        // Save current directory
        $originalDir = getcwd();

        try {
            // Change to temp directory
            chdir($this->tempDir);

            // Execute without destination argument
            $this->commandTester->execute([
                'har' => $harFile,
            ]);

            $this->assertSame(Command::SUCCESS, $this->commandTester->getStatusCode());

            // File should be created in current directory (tempDir)
            $this->assertFileExists($this->tempDir.'/1.har');
        } finally {
            // Restore original directory
            chdir($originalDir);
        }
    }

    public function testSplitPreservesHarStructure(): void
    {
        $harFile = __DIR__.'/../../fixtures/www.softwareishard.com-multiple-entries.har';

        // Load original HAR
        $serializer = new Serializer();
        $originalContents = file_get_contents($harFile);
        $originalHar = $serializer->deserializeHar($originalContents);
        $originalEntries = $originalHar->getLog()->getEntries();

        $this->commandTester->execute([
            'har' => $harFile,
            'destination' => $this->tempDir,
        ]);

        // Load split files and compare each entry
        for ($i = 0; $i < \count($originalEntries); ++$i) {
            $splitFile = $this->tempDir.'/'.($i + 1).'.har';
            $this->assertFileExists($splitFile);

            $splitContents = file_get_contents($splitFile);
            $splitHar = $serializer->deserializeHar($splitContents);
            $splitEntry = $splitHar->getLog()->getEntries()[0];

            // Compare request URLs to verify correct entry
            $this->assertEquals(
                (string) $originalEntries[$i]->getRequest()->getUrl(),
                (string) $splitEntry->getRequest()->getUrl()
            );
        }
    }

    public function testSplitFailsWhenFileDoesNotExist(): void
    {
        $nonExistentFile = $this->tempDir.'/nonexistent.har';

        $this->commandTester->execute([
            'har' => $nonExistentFile,
            'destination' => $this->tempDir,
        ]);

        // Should fail with FAILURE status
        $this->assertSame(Command::FAILURE, $this->commandTester->getStatusCode());

        // Should display ONLY "File not found" error message
        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('File not found', $output);
        $this->assertStringContainsString('nonexistent.har', $output);
        // Must NOT contain any other error messages (kills ReturnRemoval mutation)
        // Without the return, execution would continue and hit "Unable to read file"
        $this->assertStringNotContainsString('Unable to read file', $output);
    }

    public function testSplitFailsWhenPathIsDirectory(): void
    {
        // Create a directory instead of a file
        $directoryPath = $this->tempDir.'/notafile';
        mkdir($directoryPath);

        $this->commandTester->execute([
            'har' => $directoryPath,
            'destination' => $this->tempDir,
        ]);

        // Should fail with FAILURE status
        $this->assertSame(Command::FAILURE, $this->commandTester->getStatusCode());

        // Should display error message indicating it's a directory
        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('Path is a directory', $output);
        $this->assertStringContainsString('notafile', $output);
    }

    public function testSplitOutputsProgressMessages(): void
    {
        $harFile = __DIR__.'/../../fixtures/www.softwareishard.com-multiple-entries.har';

        $this->commandTester->execute([
            'har' => $harFile,
            'destination' => $this->tempDir,
        ]);

        $output = $this->commandTester->getDisplay();

        // Verify command outputs splitting message
        $this->assertStringContainsString('Splitting', $output);
        $this->assertStringContainsString('one file per entry', $output);

        // Verify progress is shown (should contain progress indicators)
        // The progress bar will show completion indicators like "[====]" or percentages
        $this->assertMatchesRegularExpression('/\d+\/\d+|\d+%|=+/', $output, 'Output should contain progress indicators');
    }

    public function testCommandConfiguration(): void
    {
        $command = new SplitCommand();

        // Verify command is properly configured
        $this->assertEquals('har:split', $command->getName());
        $this->assertStringContainsString('Split a HAR file', $command->getDescription());

        // Verify required arguments exist
        $definition = $command->getDefinition();
        $this->assertTrue($definition->hasArgument('har'));
        $this->assertTrue($definition->hasArgument('destination'));
        $this->assertTrue($definition->getArgument('har')->isRequired());
        $this->assertFalse($definition->getArgument('destination')->isRequired());

        // Verify options exist
        $this->assertTrue($definition->hasOption('md5'));
        $this->assertTrue($definition->hasOption('force'));
    }

    public function testSplitUpdatesProgressBar(): void
    {
        $harFile = __DIR__.'/../../fixtures/www.softwareishard.com-multiple-entries.har';

        // Execute the command
        $this->commandTester->execute([
            'har' => $harFile,
            'destination' => $this->tempDir,
        ]);

        $output = $this->commandTester->getDisplay();

        // Verify progress bar completes (progressFinish mutation killer)
        // The progress bar shows "11/11" and "100%" when progressFinish is called
        $this->assertMatchesRegularExpression('/11\/11/', $output, 'Progress should show 11/11 completion');
        $this->assertStringContainsString('100%', $output, 'Progress should show 100% completion');

        // Verify intermediate progress is shown (progressAdvance mutation killer)
        // Without progressAdvance(), we'd only see 0/11 then jump to 11/11
        // Check for at least one intermediate state (anything from 1/11 to 10/11)
        $this->assertMatchesRegularExpression('/[1-9]\/11|10\/11/', $output, 'Progress should show intermediate states');

        // Verify the command succeeds
        $this->assertSame(Command::SUCCESS, $this->commandTester->getStatusCode());

        // Count the actual files created
        $files = glob($this->tempDir.'/*.har');
        $this->assertCount(11, $files, 'Should create 11 files');
    }

    public function testProgressAdvanceIsCalledForEachEntry(): void
    {
        // This test kills the MethodCallRemoval mutation for progressAdvance()
        // by verifying that multiple distinct progress states are shown
        $harFile = __DIR__.'/../../fixtures/www.softwareishard.com-multiple-entries.har';

        $this->commandTester->execute([
            'har' => $harFile,
            'destination' => $this->tempDir,
        ]);

        $output = $this->commandTester->getDisplay();

        // Count how many distinct progress states are shown (e.g., "2/11", "5/11")
        // The progress bar updates on each advance, showing different counts
        preg_match_all('/(\d+)\/11/', $output, $matches);
        $progressStates = array_unique($matches[1]);

        // Without progressAdvance(), we'd only see 0/11 and 11/11 (2 states)
        // With progressAdvance(), we should see multiple intermediate states
        // Note: The exact number depends on terminal refresh rate, but should be > 2
        $this->assertGreaterThan(2, \count($progressStates),
            'Progress bar should show multiple intermediate states when progressAdvance() is called. '.
            'Found states: '.implode(', ', $progressStates));

        // Verify we start at 0 and end at 11
        $this->assertContains('0', $progressStates, 'Progress should start at 0');
        $this->assertContains('11', $progressStates, 'Progress should end at 11');
    }

    public function testProgressFinishShowsCompletion(): void
    {
        // This test kills the MethodCallRemoval mutation for progressFinish()
        // by verifying the final completion state is displayed
        $harFile = __DIR__.'/../../fixtures/www.softwareishard.com-multiple-entries.har';

        $this->commandTester->execute([
            'har' => $harFile,
            'destination' => $this->tempDir,
        ]);

        $output = $this->commandTester->getDisplay();

        // progressFinish() ensures the progress bar shows the final 100% state
        // Without it, the bar might not show 100% or the final count

        // Check for 100% completion marker
        $this->assertStringContainsString('100%', $output,
            'Progress bar must show 100% completion - this requires progressFinish() to be called');

        // Check that 11/11 appears (the final state)
        $this->assertMatchesRegularExpression('/11\/11/', $output,
            'Progress bar must show final 11/11 state - this requires progressFinish() to be called');

        // Verify there's visual progress bar completion (filled bar)
        // The progress bar uses unicode block characters to show progress
        // At 100%, the entire bar should be filled
        $this->assertMatchesRegularExpression('/[▓█=]+/', $output,
            'Progress bar must show filled progress indicator at completion');
    }

    private function recursiveRemoveDirectory(string $directory): void
    {
        if (!is_dir($directory)) {
            return;
        }

        $items = array_diff(scandir($directory), ['.', '..']);
        foreach ($items as $item) {
            $path = $directory.'/'.$item;
            if (is_dir($path)) {
                $this->recursiveRemoveDirectory($path);
            } else {
                unlink($path);
            }
        }
        rmdir($directory);
    }
}
