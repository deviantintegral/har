<?php

declare(strict_types=1);

namespace Deviantintegral\Har\Tests\Unit;

use Deviantintegral\Har\Browser;
use Deviantintegral\Har\Creator;
use Deviantintegral\Har\Log;

/**
 * @covers \Deviantintegral\Har\Log
 */
class LogTest extends HarTestBase
{
    public function testSerialize(): void
    {
        $serializer = $this->getSerializer();

        $creator = (new Creator())
          ->setName('TestCreator')
          ->setVersion('1.0');

        $browser = (new Browser())
          ->setName('TestBrowser')
          ->setVersion('2.0');

        $log = (new Log())
          ->setVersion('1.2')
          ->setCreator($creator)
          ->setBrowser($browser)
          ->setPages([])
          ->setEntries([])
          ->setComment('Test log');

        $serialized = $serializer->serialize($log, 'json');
        $decoded = json_decode($serialized, true);

        $this->assertEquals('1.2', $decoded['version']);
        $this->assertEquals('TestCreator', $decoded['creator']['name']);
        $this->assertEquals('TestBrowser', $decoded['browser']['name']);
        $this->assertEquals('Test log', $decoded['comment']);

        $deserialized = $serializer->deserialize(
            $serialized,
            Log::class,
            'json'
        );
        $this->assertEquals($log, $deserialized);
    }

    public function testGettersAndSetters(): void
    {
        $creator = (new Creator())
          ->setName('TestCreator')
          ->setVersion('1.0');

        $browser = (new Browser())
          ->setName('TestBrowser')
          ->setVersion('2.0');

        $pages = [];
        $entries = [];

        $log = (new Log())
          ->setVersion('1.2')
          ->setCreator($creator)
          ->setBrowser($browser)
          ->setPages($pages)
          ->setEntries($entries)
          ->setComment('Test comment');

        $this->assertEquals('1.2', $log->getVersion());
        $this->assertSame($creator, $log->getCreator());
        $this->assertSame($browser, $log->getBrowser());
        $this->assertEquals($pages, $log->getPages());
        $this->assertEquals($entries, $log->getEntries());
        $this->assertEquals('Test comment', $log->getComment());
    }

    public function testIso8601MicrosecondsConstant(): void
    {
        $this->assertEquals('Y-m-d\TH:i:s.uT', Log::ISO_8601_MICROSECONDS);
    }

    public function testCloneCreatorIsDeep(): void
    {
        $repository = $this->getHarFileRepository();
        $har = $repository->load('www.softwareishard.com-multiple-entries.har');
        $log = $har->getLog();

        $originalCreatorName = $log->getCreator()->getName();

        // Clone the Log
        $clonedLog = clone $log;

        // Verify the Creator object is a different instance
        $this->assertNotSame($log->getCreator(), $clonedLog->getCreator());

        // Modify the cloned log's creator name
        $clonedLog->getCreator()->setName('modified-creator');

        // Verify the original log's creator name is unchanged
        $this->assertSame($originalCreatorName, $log->getCreator()->getName());
        $this->assertSame('modified-creator', $clonedLog->getCreator()->getName());
    }

    public function testClonePagesIsDeep(): void
    {
        $repository = $this->getHarFileRepository();
        $har = $repository->load('www.softwareishard.com-multiple-entries.har');
        $log = $har->getLog();

        // Ensure we have pages to test
        $pages = $log->getPages();
        $this->assertNotEmpty($pages);

        $originalPageTitle = $pages[0]->getTitle();

        // Clone the Log
        $clonedLog = clone $log;

        // Verify the Page objects are different instances
        $this->assertNotSame($log->getPages()[0], $clonedLog->getPages()[0]);

        // Modify the cloned log's page title
        $clonedLog->getPages()[0]->setTitle('modified-title');

        // Verify the original log's page title is unchanged
        $this->assertSame($originalPageTitle, $log->getPages()[0]->getTitle());
        $this->assertSame('modified-title', $clonedLog->getPages()[0]->getTitle());
    }

    public function testCloneEntriesIsDeep(): void
    {
        $repository = $this->getHarFileRepository();
        $har = $repository->load('www.softwareishard.com-multiple-entries.har');
        $log = $har->getLog();

        $originalEntryCount = \count($log->getEntries());
        $this->assertGreaterThan(0, $originalEntryCount);

        $originalComment = $log->getEntries()[0]->getComment();

        // Clone the Log
        $clonedLog = clone $log;

        // Verify the Entry objects are different instances
        $this->assertNotSame($log->getEntries()[0], $clonedLog->getEntries()[0]);

        // Modify the cloned log's entry comment
        $clonedLog->getEntries()[0]->setComment('modified-comment');

        // Verify the original log's entry comment is unchanged
        $this->assertSame($originalComment, $log->getEntries()[0]->getComment());
        $this->assertSame('modified-comment', $clonedLog->getEntries()[0]->getComment());
    }
}
