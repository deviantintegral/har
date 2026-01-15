<?php

declare(strict_types=1);

namespace Deviantintegral\Har;

use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * A PSR-18 HTTP client decorator that records request/response traffic to HAR format.
 *
 * Use case: Generate test fixtures by recording real API interactions.
 *
 * Example usage:
 *     $recorder = new HarRecorder($actualHttpClient);
 *     $response = $recorder->sendRequest($request);  // Makes real request
 *     $har = $recorder->getHar();  // Get recorded traffic
 */
final class HarRecorder implements ClientInterface
{
    /**
     * @var Entry[]
     */
    private array $entries = [];

    private Creator $creator;

    /**
     * @param ClientInterface $client         The underlying HTTP client to delegate requests to
     * @param string          $creatorName    Name of the application creating the HAR
     * @param string          $creatorVersion Version of the application
     */
    public function __construct(
        private readonly ClientInterface $client,
        string $creatorName = 'deviantintegral/har',
        string $creatorVersion = '1.0',
    ) {
        $this->creator = (new Creator())
            ->setName($creatorName)
            ->setVersion($creatorVersion);
    }

    /**
     * Send an HTTP request and record the request/response pair.
     *
     * @throws \Psr\Http\Client\ClientExceptionInterface
     */
    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        $startTime = hrtime(true);
        $startDateTime = new \DateTime();

        $response = $this->client->sendRequest($request);

        $endTime = hrtime(true);
        /** @infection-ignore-all Equivalent mutant: 1 part per million difference is not testable */
        $totalTimeMs = ($endTime - $startTime) / 1_000_000;

        $entry = $this->createEntry($request, $response, $startDateTime, $totalTimeMs);
        $this->entries[] = $entry;

        return $response;
    }

    /**
     * Get the recorded traffic as a HAR object.
     */
    public function getHar(): Har
    {
        $log = (new Log())
            ->setVersion('1.2')
            ->setCreator($this->creator)
            ->setEntries($this->entries);

        return (new Har())->setLog($log);
    }

    /**
     * Clear all recorded entries.
     */
    public function clear(): void
    {
        $this->entries = [];
    }

    /**
     * Create a HAR entry from the request/response pair.
     */
    private function createEntry(
        RequestInterface $request,
        ResponseInterface $response,
        \DateTime $startDateTime,
        float $totalTimeMs,
    ): Entry {
        $harRequest = Request::fromPsr7Request($request);
        $harResponse = Response::fromPsr7Response($response);

        $timings = (new Timings())
            ->setSend(0)
            ->setWait($totalTimeMs)
            ->setReceive(0);

        return (new Entry())
            ->setStartedDateTime($startDateTime)
            ->setTime($totalTimeMs)
            ->setRequest($harRequest)
            ->setResponse($harResponse)
            ->setCache(new Cache())
            ->setTimings($timings);
    }
}
