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
    private HarFileRepository $repository;

    protected function setUp(): void
    {
        // Initialize a repository of HAR files, with IDs being the file names.
        $this->repository = new HarFileRepository(__DIR__.'/../../fixtures');
    }

    public function testHasInitiator(): void
    {
        // Load a HAR file into an object.
        $har = $this->repository->load('www.softwareishard.com-empty-login.har');
        $first = $har->getLog()->getEntries()[0];
        $this->assertFalse($first->hasInitiator());
        $first->setInitiator((new Initiator())->setType('other'));
        $this->assertTrue($first->hasInitiator());
    }

    public function testGetSetPageref(): void
    {
        $entry = (new Entry())->setPageref('page_1');
        $this->assertEquals('page_1', $entry->getPageref());
    }

    public function testGetSetTime(): void
    {
        $entry = (new Entry())->setTime(123.45);
        $this->assertEquals(123.45, $entry->getTime());
    }

    public function testGetSetRequest(): void
    {
        $request = new Request();
        $entry = (new Entry())->setRequest($request);
        $this->assertSame($request, $entry->getRequest());
    }

    public function testGetSetResponse(): void
    {
        $response = new Response();
        $entry = (new Entry())->setResponse($response);
        $this->assertSame($response, $entry->getResponse());
    }

    public function testGetSetCache(): void
    {
        $cache = new Cache();
        $entry = (new Entry())->setCache($cache);
        $this->assertSame($cache, $entry->getCache());
    }

    public function testGetSetTimings(): void
    {
        $timings = new Timings();
        $entry = (new Entry())->setTimings($timings);
        $this->assertSame($timings, $entry->getTimings());
    }

    public function testGetSetServerIPAddress(): void
    {
        $entry = (new Entry())->setServerIPAddress('192.168.1.1');
        $this->assertEquals('192.168.1.1', $entry->getServerIPAddress());
    }

    public function testGetSetConnection(): void
    {
        $entry = (new Entry())->setConnection('12345');
        $this->assertEquals('12345', $entry->getConnection());
    }

    public function testGetSetInitiator(): void
    {
        $initiator = (new Initiator())->setType('parser');
        $entry = (new Entry())->setInitiator($initiator);
        $this->assertSame($initiator, $entry->getInitiator());
    }
}
