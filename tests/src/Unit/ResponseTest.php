<?php

declare(strict_types=1);

namespace Deviantintegral\Har\Tests\Unit;

use Deviantintegral\Har\Header;
use GuzzleHttp\Psr7\Response;

class ResponseTest extends HarTestBase
{
    public function testFromPsr7()
    {
        $psr7 = new Response(200, ['Content-Type' => 'text/plain'], 'testing', '2.0', 'Who needs reasons?');
        $response = \Deviantintegral\Har\Response::fromPsr7Response($psr7);
        $this->assertEquals(200, $response->getStatus());
        $this->assertEquals([
          (new Header())->setName('Content-Type')->setValue('text/plain'),
        ], $response->getHeaders());
        $this->assertEquals('testing', $response->getContent()->getText());
        $this->assertEquals('HTTP/2.0', $response->getHttpVersion());
        $this->assertEquals('Who needs reasons?', $response->getStatusText());
    }
}
