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

    public function testCloneIsDeep(): void
    {
        $har = $this->repository->load('www.softwareishard.com-single-entry.har');
        $entry = $har->getLog()->getEntries()[0];

        // Clone the entry
        $cloned = clone $entry;

        // Verify the cloned request is a different instance
        $this->assertNotSame($entry->getRequest(), $cloned->getRequest());
        $this->assertNotSame($entry->getResponse(), $cloned->getResponse());

        // Modify the cloned request
        $cloned->getRequest()->setMethod('PATCH');

        // Verify the original is unchanged
        $this->assertNotEquals('PATCH', $entry->getRequest()->getMethod());
    }

    public function testCloneWithInitiator(): void
    {
        $entry = (new Entry())
            ->setRequest(new Request())
            ->setResponse(new Response())
            ->setCache(new Cache())
            ->setTimings(new Timings())
            ->setInitiator((new Initiator())->setType('parser'));

        $cloned = clone $entry;

        // Verify initiator is cloned
        $this->assertNotSame($entry->getInitiator(), $cloned->getInitiator());

        // Modify cloned initiator
        $cloned->getInitiator()->setType('script');

        // Verify original is unchanged
        $this->assertEquals('parser', $entry->getInitiator()->getType());
    }

    public function testCloneCacheIsDeep(): void
    {
        $cache = (new Cache())->setComment('original comment');
        $entry = (new Entry())
            ->setRequest(new Request())
            ->setResponse(new Response())
            ->setCache($cache)
            ->setTimings(new Timings());

        $cloned = clone $entry;

        // Verify cache is cloned (different instance)
        $this->assertNotSame($entry->getCache(), $cloned->getCache());

        // Modify cloned cache
        $cloned->getCache()->setComment('modified comment');

        // Verify original is unchanged
        $this->assertEquals('original comment', $entry->getCache()->getComment());
    }

    public function testCloneTimingsIsDeep(): void
    {
        $timings = (new Timings())
            ->setBlocked(10.0)
            ->setDns(20.0)
            ->setSsl(-1)
            ->setConnect(-1)
            ->setSend(5.0)
            ->setWait(100.0)
            ->setReceive(15.0);
        $entry = (new Entry())
            ->setRequest(new Request())
            ->setResponse(new Response())
            ->setCache(new Cache())
            ->setTimings($timings);

        $cloned = clone $entry;

        // Verify timings is cloned (different instance)
        $this->assertNotSame($entry->getTimings(), $cloned->getTimings());

        // Modify cloned timings
        $cloned->getTimings()->setBlocked(99.0);

        // Verify original is unchanged
        $this->assertEquals(10.0, $entry->getTimings()->getBlocked());
    }
}
