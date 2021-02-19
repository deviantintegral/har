<?php

declare(strict_types=1);

namespace Deviantintegral\Har\Tests\Unit;

use Deviantintegral\Har\Initiator;
use Deviantintegral\Har\Repository\HarFileRepository;

/**
 * @covers \Deviantintegral\Har\Entry
 */
class EntryTest extends HarTestBase
{
    private $repository;

    protected function setUp(): void
    {
        // Initialize a repository of HAR files, with IDs being the file names.
        $this->repository = new HarFileRepository(__DIR__.'/../../fixtures');
    }

    public function testHasInitiator()
    {
        // Load a HAR file into an object.
        $har = $this->repository->load('www.softwareishard.com-empty-login.har');
        $first = $har->getLog()->getEntries()[0];
        $this->assertFalse($first->hasInitiator());
        $first->setInitiator((new Initiator())->setType('other'));
        $this->assertTrue($first->hasInitiator());
    }

    public function testSerializationOfEntryWithAddedInitiatorOfTypeOther()
    {
        // Load a HAR file into an object.
        $id = 'www.softwareishard.com-empty-login.har';
        $har = $this->repository->load($id);
        $first = $har->getLog()->getEntries()[0];
        $first->setInitiator((new Initiator())->setType('other'));

        $serialized = $this->getSerializer()->serialize($har, 'json');
        $actual = json_decode($serialized, true);
        $this->assertIsArray($actual['log']['entries'][0]['_initiator']);
        $this->assertEquals('other', $actual['log']['entries'][0]['_initiator']['type']);
        $this->assertArrayNotHasKey('url', $actual['log']['entries'][0]['_initiator']);
        $this->assertArrayNotHasKey('lineNumber', $actual['log']['entries'][0]['_initiator']);

        $this->assertArrayNotHasKey('_initiator', $actual['log']['entries'][1]);
    }
}
