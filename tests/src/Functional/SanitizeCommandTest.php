<?php

declare(strict_types=1);

namespace Deviantintegral\Har\Tests\Functional;

use Deviantintegral\Har\Command\SanitizeCommand;
use Deviantintegral\Har\Content;
use Deviantintegral\Har\Creator;
use Deviantintegral\Har\Entry;
use Deviantintegral\Har\Har;
use Deviantintegral\Har\Log;
use Deviantintegral\Har\Params;
use Deviantintegral\Har\PostData;
use Deviantintegral\Har\Request;
use Deviantintegral\Har\Response;
use Deviantintegral\Har\Serializer;
use Deviantintegral\Har\Tests\Unit\HarTestBase;
use GuzzleHttp\Psr7\Uri;
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
        $this->assertTrue($definition->hasOption('query-param'));
        $this->assertTrue($definition->getOption('query-param')->isArray());
        $this->assertTrue($definition->hasOption('body-field'));
        $this->assertTrue($definition->getOption('body-field')->isArray());
        $this->assertTrue($definition->hasOption('case-sensitive'));
        $this->assertFalse($definition->getOption('case-sensitive')->isArray());
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

    public function testSanitizeQueryParams(): void
    {
        $harFile = $this->createHarFileWithQueryParams([
            'api_key' => 'secret-key-123',
            'token' => 'auth-token-456',
            'page' => '1',
        ]);
        $outputFile = $this->tempDir.'/sanitized.har';

        $this->commandTester->execute([
            'har' => $harFile,
            'output' => $outputFile,
            '--query-param' => ['api_key', 'token'],
        ]);

        $this->assertSame(Command::SUCCESS, $this->commandTester->getStatusCode());

        $serializer = new Serializer();
        $sanitized = $serializer->deserializeHar(file_get_contents($outputFile));

        $params = $sanitized->getLog()->getEntries()[0]->getRequest()->getQueryString();
        $paramMap = $this->paramsToMap($params);

        $this->assertEquals('[REDACTED]', $paramMap['api_key']);
        $this->assertEquals('[REDACTED]', $paramMap['token']);
        $this->assertEquals('1', $paramMap['page']);
    }

    public function testSanitizeMultipleQueryParams(): void
    {
        $harFile = $this->createHarFileWithQueryParams([
            'secret1' => 'value1',
            'secret2' => 'value2',
            'secret3' => 'value3',
            'public' => 'visible',
        ]);
        $outputFile = $this->tempDir.'/sanitized.har';

        $this->commandTester->execute([
            'har' => $harFile,
            'output' => $outputFile,
            '--query-param' => ['secret1', 'secret2', 'secret3'],
        ]);

        $this->assertSame(Command::SUCCESS, $this->commandTester->getStatusCode());

        $serializer = new Serializer();
        $sanitized = $serializer->deserializeHar(file_get_contents($outputFile));

        $params = $sanitized->getLog()->getEntries()[0]->getRequest()->getQueryString();
        $paramMap = $this->paramsToMap($params);

        $this->assertEquals('[REDACTED]', $paramMap['secret1']);
        $this->assertEquals('[REDACTED]', $paramMap['secret2']);
        $this->assertEquals('[REDACTED]', $paramMap['secret3']);
        $this->assertEquals('visible', $paramMap['public']);
    }

    public function testSanitizeHeadersAndQueryParamsTogether(): void
    {
        $harFile = $this->createHarFileWithQueryParams([
            'api_key' => 'secret-key',
            'page' => '1',
        ]);
        $outputFile = $this->tempDir.'/sanitized.har';

        $this->commandTester->execute([
            'har' => $harFile,
            'output' => $outputFile,
            '--header' => ['Host'],
            '--query-param' => ['api_key'],
        ]);

        $this->assertSame(Command::SUCCESS, $this->commandTester->getStatusCode());

        $serializer = new Serializer();
        $sanitized = $serializer->deserializeHar(file_get_contents($outputFile));

        $entry = $sanitized->getLog()->getEntries()[0];

        // Check query params are redacted
        $params = $entry->getRequest()->getQueryString();
        $paramMap = $this->paramsToMap($params);
        $this->assertEquals('[REDACTED]', $paramMap['api_key']);
        $this->assertEquals('1', $paramMap['page']);

        // Check headers are redacted
        $headers = $entry->getRequest()->getHeaders();
        $headerMap = $this->headersToMap($headers);
        if (isset($headerMap['Host'])) {
            $this->assertEquals('[REDACTED]', $headerMap['Host']);
        }
    }

    public function testSanitizeBodyFields(): void
    {
        $harFile = $this->createHarFileWithPostData([
            'username' => 'john',
            'password' => 'secret123',
            'remember_me' => 'true',
        ]);
        $outputFile = $this->tempDir.'/sanitized.har';

        $this->commandTester->execute([
            'har' => $harFile,
            'output' => $outputFile,
            '--body-field' => ['password'],
        ]);

        $this->assertSame(Command::SUCCESS, $this->commandTester->getStatusCode());

        $serializer = new Serializer();
        $sanitized = $serializer->deserializeHar(file_get_contents($outputFile));

        $postData = $sanitized->getLog()->getEntries()[0]->getRequest()->getPostData();
        $params = $postData->getParams();
        $paramMap = $this->paramsToMap($params);

        $this->assertEquals('john', $paramMap['username']);
        $this->assertEquals('[REDACTED]', $paramMap['password']);
        $this->assertEquals('true', $paramMap['remember_me']);
    }

    public function testSanitizeMultipleBodyFields(): void
    {
        $harFile = $this->createHarFileWithPostData([
            'username' => 'john',
            'password' => 'secret123',
            'api_key' => 'key-456',
            'public_field' => 'visible',
        ]);
        $outputFile = $this->tempDir.'/sanitized.har';

        $this->commandTester->execute([
            'har' => $harFile,
            'output' => $outputFile,
            '--body-field' => ['password', 'api_key'],
        ]);

        $this->assertSame(Command::SUCCESS, $this->commandTester->getStatusCode());

        $serializer = new Serializer();
        $sanitized = $serializer->deserializeHar(file_get_contents($outputFile));

        $postData = $sanitized->getLog()->getEntries()[0]->getRequest()->getPostData();
        $params = $postData->getParams();
        $paramMap = $this->paramsToMap($params);

        $this->assertEquals('john', $paramMap['username']);
        $this->assertEquals('[REDACTED]', $paramMap['password']);
        $this->assertEquals('[REDACTED]', $paramMap['api_key']);
        $this->assertEquals('visible', $paramMap['public_field']);
    }

    public function testSanitizeAllOptionsTogether(): void
    {
        $harFile = $this->createHarFileWithAllData(
            ['token' => 'secret-token', 'page' => '1'],
            ['password' => 'secret123', 'username' => 'john']
        );
        $outputFile = $this->tempDir.'/sanitized.har';

        $this->commandTester->execute([
            'har' => $harFile,
            'output' => $outputFile,
            '--header' => ['Host'],
            '--query-param' => ['token'],
            '--body-field' => ['password'],
        ]);

        $this->assertSame(Command::SUCCESS, $this->commandTester->getStatusCode());

        $serializer = new Serializer();
        $sanitized = $serializer->deserializeHar(file_get_contents($outputFile));

        $entry = $sanitized->getLog()->getEntries()[0];

        // Check query params
        $queryParams = $entry->getRequest()->getQueryString();
        $queryMap = $this->paramsToMap($queryParams);
        $this->assertEquals('[REDACTED]', $queryMap['token']);
        $this->assertEquals('1', $queryMap['page']);

        // Check body fields
        $postData = $entry->getRequest()->getPostData();
        $bodyParams = $postData->getParams();
        $bodyMap = $this->paramsToMap($bodyParams);
        $this->assertEquals('[REDACTED]', $bodyMap['password']);
        $this->assertEquals('john', $bodyMap['username']);
    }

    public function testCaseInsensitiveMatchingByDefault(): void
    {
        $harFile = $this->createHarFileWithQueryParams([
            'API_KEY' => 'secret-key',
            'Token' => 'auth-token',
        ]);
        $outputFile = $this->tempDir.'/sanitized.har';

        // By default, matching is case-insensitive
        $this->commandTester->execute([
            'har' => $harFile,
            'output' => $outputFile,
            '--query-param' => ['api_key', 'token'],
        ]);

        $this->assertSame(Command::SUCCESS, $this->commandTester->getStatusCode());

        $serializer = new Serializer();
        $sanitized = $serializer->deserializeHar(file_get_contents($outputFile));

        $params = $sanitized->getLog()->getEntries()[0]->getRequest()->getQueryString();
        $paramMap = $this->paramsToMap($params);

        // Both should be redacted despite case mismatch
        $this->assertEquals('[REDACTED]', $paramMap['API_KEY']);
        $this->assertEquals('[REDACTED]', $paramMap['Token']);
    }

    public function testCaseSensitiveMatchingWhenEnabled(): void
    {
        $harFile = $this->createHarFileWithQueryParams([
            'API_KEY' => 'secret-key',
            'api_key' => 'another-key',
            'Token' => 'auth-token',
        ]);
        $outputFile = $this->tempDir.'/sanitized.har';

        // With case-sensitive enabled, only exact matches should be redacted
        $this->commandTester->execute([
            'har' => $harFile,
            'output' => $outputFile,
            '--query-param' => ['api_key'],
            '--case-sensitive' => true,
        ]);

        $this->assertSame(Command::SUCCESS, $this->commandTester->getStatusCode());

        $serializer = new Serializer();
        $sanitized = $serializer->deserializeHar(file_get_contents($outputFile));

        $params = $sanitized->getLog()->getEntries()[0]->getRequest()->getQueryString();
        $paramMap = $this->paramsToMap($params);

        // Only exact case match should be redacted
        $this->assertEquals('secret-key', $paramMap['API_KEY']);
        $this->assertEquals('[REDACTED]', $paramMap['api_key']);
        $this->assertEquals('auth-token', $paramMap['Token']);
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

    /**
     * @param Params[] $params
     *
     * @return array<string, string>
     */
    private function paramsToMap(array $params): array
    {
        $map = [];
        foreach ($params as $param) {
            $map[$param->getName()] = $param->getValue();
        }

        return $map;
    }

    /**
     * @param array<string, string> $queryParams
     */
    private function createHarFileWithQueryParams(array $queryParams): string
    {
        $paramObjects = [];
        foreach ($queryParams as $name => $value) {
            $param = (new Params())->setName($name)->setValue($value);
            $paramObjects[] = $param;
        }

        $request = (new Request())
            ->setMethod('GET')
            ->setUrl(new Uri('https://example.com/api'))
            ->setHttpVersion('HTTP/1.1')
            ->setHeaders([])
            ->setCookies([])
            ->setQueryString($paramObjects)
            ->setHeadersSize(-1)
            ->setBodySize(0);

        $content = (new Content())
            ->setSize(0)
            ->setMimeType('text/html');

        $response = (new Response())
            ->setStatus(200)
            ->setStatusText('OK')
            ->setHttpVersion('HTTP/1.1')
            ->setHeaders([])
            ->setCookies([])
            ->setContent($content)
            ->setHeadersSize(-1)
            ->setBodySize(0);

        $entry = (new Entry())
            ->setStartedDateTime(new \DateTime())
            ->setTime(100)
            ->setRequest($request)
            ->setResponse($response);

        $creator = (new Creator())
            ->setName('Test')
            ->setVersion('1.0');

        $log = (new Log())
            ->setVersion('1.2')
            ->setCreator($creator)
            ->setEntries([$entry]);

        $har = (new Har())->setLog($log);

        $serializer = new Serializer();
        $harContent = $serializer->serializeHar($har);

        $filePath = $this->tempDir.'/input-'.uniqid().'.har';
        file_put_contents($filePath, $harContent);

        return $filePath;
    }

    /**
     * @param array<string, string> $postParams
     */
    private function createHarFileWithPostData(array $postParams): string
    {
        $paramObjects = [];
        foreach ($postParams as $name => $value) {
            $param = (new Params())->setName($name)->setValue($value);
            $paramObjects[] = $param;
        }

        $postData = (new PostData())
            ->setMimeType('application/x-www-form-urlencoded')
            ->setParams($paramObjects);

        $request = (new Request())
            ->setMethod('POST')
            ->setUrl(new Uri('https://example.com/api'))
            ->setHttpVersion('HTTP/1.1')
            ->setHeaders([])
            ->setCookies([])
            ->setQueryString([])
            ->setPostData($postData)
            ->setHeadersSize(-1)
            ->setBodySize(0);

        $content = (new Content())
            ->setSize(0)
            ->setMimeType('text/html');

        $response = (new Response())
            ->setStatus(200)
            ->setStatusText('OK')
            ->setHttpVersion('HTTP/1.1')
            ->setHeaders([])
            ->setCookies([])
            ->setContent($content)
            ->setHeadersSize(-1)
            ->setBodySize(0);

        $entry = (new Entry())
            ->setStartedDateTime(new \DateTime())
            ->setTime(100)
            ->setRequest($request)
            ->setResponse($response);

        $creator = (new Creator())
            ->setName('Test')
            ->setVersion('1.0');

        $log = (new Log())
            ->setVersion('1.2')
            ->setCreator($creator)
            ->setEntries([$entry]);

        $har = (new Har())->setLog($log);

        $serializer = new Serializer();
        $harContent = $serializer->serializeHar($har);

        $filePath = $this->tempDir.'/input-'.uniqid().'.har';
        file_put_contents($filePath, $harContent);

        return $filePath;
    }

    /**
     * @param array<string, string> $queryParams
     * @param array<string, string> $postParams
     */
    private function createHarFileWithAllData(array $queryParams, array $postParams): string
    {
        $queryParamObjects = [];
        foreach ($queryParams as $name => $value) {
            $param = (new Params())->setName($name)->setValue($value);
            $queryParamObjects[] = $param;
        }

        $postParamObjects = [];
        foreach ($postParams as $name => $value) {
            $param = (new Params())->setName($name)->setValue($value);
            $postParamObjects[] = $param;
        }

        $postData = (new PostData())
            ->setMimeType('application/x-www-form-urlencoded')
            ->setParams($postParamObjects);

        $request = (new Request())
            ->setMethod('POST')
            ->setUrl(new Uri('https://example.com/api'))
            ->setHttpVersion('HTTP/1.1')
            ->setHeaders([])
            ->setCookies([])
            ->setQueryString($queryParamObjects)
            ->setPostData($postData)
            ->setHeadersSize(-1)
            ->setBodySize(0);

        $content = (new Content())
            ->setSize(0)
            ->setMimeType('text/html');

        $response = (new Response())
            ->setStatus(200)
            ->setStatusText('OK')
            ->setHttpVersion('HTTP/1.1')
            ->setHeaders([])
            ->setCookies([])
            ->setContent($content)
            ->setHeadersSize(-1)
            ->setBodySize(0);

        $entry = (new Entry())
            ->setStartedDateTime(new \DateTime())
            ->setTime(100)
            ->setRequest($request)
            ->setResponse($response);

        $creator = (new Creator())
            ->setName('Test')
            ->setVersion('1.0');

        $log = (new Log())
            ->setVersion('1.2')
            ->setCreator($creator)
            ->setEntries([$entry]);

        $har = (new Har())->setLog($log);

        $serializer = new Serializer();
        $content = $serializer->serializeHar($har);

        $filePath = $this->tempDir.'/input-'.uniqid().'.har';
        file_put_contents($filePath, $content);

        return $filePath;
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
