<?php

declare(strict_types=1);

namespace Deviantintegral\Har\Tests\Unit;

use Deviantintegral\Har\Content;

/**
 * @covers \Deviantintegral\Har\Content
 * @covers \Deviantintegral\Har\MimeTypeTrait
 */
class ContentTest extends HarTestBase
{
    public function testSerialize()
    {
        $serializer = $this->getSerializer();
        $text = base64_encode('testing');
        $content = (new Content())
          ->setEncoding('base64')
          ->setMimeType('text/plain')
          ->setText($text)
          ->setSize(\strlen($text))
          ->setNumber(1);

        $serialized = $serializer->serialize($content, 'json');
        $this->assertEquals(
          [
            'size' => $content->getSize(),
            'number' => $content->getNumber(),
            'mimeType' => $content->getMimeType(),
            'text' => $content->getText(),
            'encoding' => $content->getEncoding(),
          ],
          json_decode($serialized, true)
        );

        $this->assertDeserialize($serialized, Content::class, $content);
    }
}
