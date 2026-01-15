<?php

declare(strict_types=1);

namespace Deviantintegral\Har\Tests\Functional;

use Deviantintegral\Har\Command\SanitizeCommand;
use Deviantintegral\Har\Serializer;
use Deviantintegral\Har\Tests\Unit\HarTestBase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class SanitizeCommandTest extends HarTestBase
{
    private string $tempDir;
    private CommandTester $commandTester;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tempDir = sys_get_temp_dir().'/har_sanitize_test_'.uniqid();
        mkdir($this->tempDir, recursive: true);

        $command = new SanitizeCommand();
        $this->commandTester = new CommandTester($command);
    }

    protected function tearDown(): void
    {
        if (is_dir($this->tempDir)) {
            $this->recursiveRemoveDirectory($this->tempDir);
        }

        parent::tearDown();
    }

    public function testSanitizeHeadersToFile(): void
    {
        $harFile = __DIR__.'/../../fixtures/www.softwareishard.com-single-entry.har';
        $outputFile = $this->tempDir.'/sanitized.har';

        $this->commandTester->execute([
            'har' => $harFile,
            'output' => $outputFile,
            '--header' => ['Accept-Encoding', 'User-Agent'],
        ]);

        $this->assertSame(Command::SUCCESS, $this->commandTester->getStatusCode());
        $this->assertFileExists($outputFile);

        $serializer = new Serializer();
        $sanitized = $serializer->deserializeHar(file_get_contents($outputFile));

        $headers = $sanitized->getLog()->getEntries()[0]->getRequest()->getHeaders();
        $headerMap = $this->headersToMap($headers);

        if (isset($headerMap['Accept-Encoding'])) {
            $this->assertEquals('[REDACTED]', $headerMap['Accept-Encoding']);
        }
        if (isset($headerMap['User-Agent'])) {
            $this->assertEquals('[REDACTED]', $headerMap['User-Agent']);
        }
    }

    public function testSanitizeHeadersToStdout(): void
    {
        $harFile = __DIR__.'/../../fixtures/www.softwareishard.com-single-entry.har';

        $this->commandTester->execute([
            'har' => $harFile,
            '--header' => ['Accept-Encoding'],
        ]);

        $this->assertSame(Command::SUCCESS, $this->commandTester->getStatusCode());

        $output = $this->commandTester->getDisplay();
        $this->assertJson($output);

        $serializer = new Serializer();
        $sanitized = $serializer->deserializeHar($output);

        $headers = $sanitized->getLog()->getEntries()[0]->getRequest()->getHeaders();
        $headerMap = $this->headersToMap($headers);

        if (isset($headerMap['Accept-Encoding'])) {
            $this->assertEquals('[REDACTED]', $headerMap['Accept-Encoding']);
        }
    }

    public function testSanitizeMultipleHeaders(): void
    {
        $harFile = __DIR__.'/../../fixtures/www.softwareishard.com-single-entry.har';
        $outputFile = $this->tempDir.'/sanitized.har';

        $this->commandTester->execute([
            'har' => $harFile,
            'output' => $outputFile,
            '--header' => ['Accept-Encoding', 'Accept-Language', 'Host'],
        ]);

        $this->assertSame(Command::SUCCESS, $this->commandTester->getStatusCode());

        $serializer = new Serializer();
        $sanitized = $serializer->deserializeHar(file_get_contents($outputFile));

        $headers = $sanitized->getLog()->getEntries()[0]->getRequest()->getHeaders();
        $headerMap = $this->headersToMap($headers);

        // All specified headers should be redacted
        foreach (['Accept-Encoding', 'Accept-Language', 'Host'] as $headerName) {
            if (isset($headerMap[$headerName])) {
                $this->assertEquals('[REDACTED]', $headerMap[$headerName], "Header $headerName should be redacted");
            }
        }
    }

    public function testSanitizeFailsWhenFileNotFound(): void
    {
        $nonExistentFile = $this->tempDir.'/nonexistent.har';

        $this->commandTester->execute([
            'har' => $nonExistentFile,
        ]);

        $this->assertSame(Command::FAILURE, $this->commandTester->getStatusCode());

        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('File not found', $output);
    }

    public function testSanitizeFailsWhenPathIsDirectory(): void
    {
        $directoryPath = $this->tempDir.'/notafile';
        mkdir($directoryPath);

        $this->commandTester->execute([
            'har' => $directoryPath,
        ]);

        $this->assertSame(Command::FAILURE, $this->commandTester->getStatusCode());

        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('Path is a directory', $output);
    }

    public function testSanitizeShowsSuccessMessage(): void
    {
        $harFile = __DIR__.'/../../fixtures/www.softwareishard.com-single-entry.har';
        $outputFile = $this->tempDir.'/sanitized.har';

        $this->commandTester->execute([
            'har' => $harFile,
            'output' => $outputFile,
            '--header' => ['Accept-Encoding'],
        ]);

        $this->assertSame(Command::SUCCESS, $this->commandTester->getStatusCode());

        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('Sanitized HAR written to', $output);
        $this->assertStringContainsString($outputFile, $output);
    }

    public function testCommandConfiguration(): void
    {
        $command = new SanitizeCommand();

        $this->assertEquals('har:sanitize', $command->getName());
        $this->assertStringContainsString('Sanitize sensitive data', $command->getDescription());

        $definition = $command->getDefinition();
        $this->assertTrue($definition->getArgument('har')->isRequired());
        $this->assertFalse($definition->getArgument('output')->isRequired());
        $this->assertTrue($definition->hasOption('header'));
        $this->assertTrue($definition->getOption('header')->isArray());
    }

    public function testSanitizeWithNoOptions(): void
    {
        $harFile = __DIR__.'/../../fixtures/www.softwareishard.com-single-entry.har';
        $outputFile = $this->tempDir.'/sanitized.har';

        // Load original for comparison
        $serializer = new Serializer();
        $original = $serializer->deserializeHar(file_get_contents($harFile));

        $this->commandTester->execute([
            'har' => $harFile,
            'output' => $outputFile,
        ]);

        $this->assertSame(Command::SUCCESS, $this->commandTester->getStatusCode());

        // With no options, output should be functionally equivalent to input
        $sanitized = $serializer->deserializeHar(file_get_contents($outputFile));
        $this->assertCount(
            \count($original->getLog()->getEntries()),
            $sanitized->getLog()->getEntries()
        );
    }

    /**
     * @param \Deviantintegral\Har\Header[] $headers
     *
     * @return array<string, string>
     */
    private function headersToMap(array $headers): array
    {
        $map = [];
        foreach ($headers as $header) {
            $map[$header->getName()] = $header->getValue();
        }

        return $map;
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
