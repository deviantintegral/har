<?php

declare(strict_types=1);

namespace Deviantintegral\Har\Tests\Unit;

use Deviantintegral\Har\Adapter\Psr7\Request;
use Deviantintegral\Har\Entry;
use Deviantintegral\Har\Har;
use Deviantintegral\Har\Log;
use Deviantintegral\Har\Repository\HarFileRepository;
use Deviantintegral\Har\Response;
use Deviantintegral\Har\Serializer;
use GuzzleHttp\Client;
use PHPUnit\Framework\TestCase;

class ReadmeTest extends TestCase
{
    public function testExample()
    {
        // Initialize a repository of HAR files, with IDs being the file names.
        $repository = new HarFileRepository(__DIR__.'/../../fixtures');

        // Load a HAR file into an object.
        $har = $repository->load('www.softwareishard.com-single-entry.har');

        // Each log can have multiple entries, and we know this file has exactly
        // one.
        $entry = $har->getLog()->getEntries()[0];

        // Convert the captured request into something we can send with Guzzle.
        $psr7_request = new Request($entry->getRequest());

        // Send the request live!
        try {
            $psr7_response = (new Client())->send($psr7_request);

            // Convert the response back to a HAR response object.
            $response = Response::fromPsr7Response($psr7_response);

            // At this point, you can either assert the whole response object,
            // or assert parts of it depending on what you are trying to test.
            $this->assertEquals($entry->getResponse()->getStatus(), $response->getStatus());

            // Create a new HAR with the old request paired with the new
            // response and serialize it to JSON.
            $response_har = (new Har())
              ->setLog((new Log())
              ->setEntries([
                (new Entry())
                ->setRequest($entry->getRequest())
                ->setResponse($response),
              ]));
            $serialized = (new Serializer())->serializeHar($response_har);
            self::assertJson($serialized);
        } catch (\Exception $e) {
            // Since we don't control the above server, we don't fail this
            // test.
            $this->markTestSkipped(sprintf('%s: %s %s', $e::class, $e->getCode(), $e->getMessage()));
        }
    }
}
