<?php

declare(strict_types=1);

namespace Deviantintegral\Har\Tests\Unit;

use Deviantintegral\Har\Cache;
use Deviantintegral\Har\Entry;
use Deviantintegral\Har\Initiator;
use Deviantintegral\Har\Repository\HarFileRepository;
use Deviantintegral\Har\Request;
use Deviantintegral\Har\Response;
use Deviantintegral\Har\Timings;

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

    public function testGetSetPageref()
    {
        $entry = (new Entry())->setPageref('page_1');
        $this->assertEquals('page_1', $entry->getPageref());
    }

    public function testGetSetTime()
    {
        $entry = (new Entry())->setTime(123.45);
        $this->assertEquals(123.45, $entry->getTime());
    }

    public function testGetSetRequest()
    {
        $request = new Request();
        $entry = (new Entry())->setRequest($request);
        $this->assertSame($request, $entry->getRequest());
    }

    public function testGetSetResponse()
    {
        $response = new Response();
        $entry = (new Entry())->setResponse($response);
        $this->assertSame($response, $entry->getResponse());
    }

    public function testGetSetCache()
    {
        $cache = new Cache();
        $entry = (new Entry())->setCache($cache);
        $this->assertSame($cache, $entry->getCache());
    }

    public function testGetSetTimings()
    {
        $timings = new Timings();
        $entry = (new Entry())->setTimings($timings);
        $this->assertSame($timings, $entry->getTimings());
    }

    public function testGetSetServerIPAddress()
    {
        $entry = (new Entry())->setServerIPAddress('192.168.1.1');
        $this->assertEquals('192.168.1.1', $entry->getServerIPAddress());
    }

    public function testGetSetConnection()
    {
        $entry = (new Entry())->setConnection('12345');
        $this->assertEquals('12345', $entry->getConnection());
    }

    public function testGetSetInitiator()
    {
        $initiator = (new Initiator())->setType('parser');
        $entry = (new Entry())->setInitiator($initiator);
        $this->assertSame($initiator, $entry->getInitiator());
    }
}
