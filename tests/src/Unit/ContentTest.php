<?php

declare(strict_types=1);

namespace Deviantintegral\Har\Tests\Unit;

use Deviantintegral\Har\Content;

/**
 * @covers \Deviantintegral\Har\Content
 * @covers \Deviantintegral\Har\SharedFields\MimeTypeTrait
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

        $this->assertTrue($content->hasEncoding());
        $this->assertNotNull($content->getEncoding());
        $this->assertTrue($content->hasNumber());
        $this->assertNotNull($content->getNumber());

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

    public function testSerializeWithoutOptionalAttributes()
    {
        $serializer = $this->getSerializer();
        $text = 'testing not encoded';
        $content = (new Content())
            ->setMimeType('text/plain')
            ->setText($text)
            ->setSize(\strlen($text));

        $this->assertFalse($content->hasEncoding());
        $this->assertNull($content->getEncoding());
        $this->assertFalse($content->hasNumber());
        $this->assertNull($content->getNumber());

        $serialized = $serializer->serialize($content, 'json');
        $this->assertEquals(
            [
                'mimeType' => $content->getMimeType(),
                'size' => $content->getSize(),
                'text' => $content->getText(),
            ],
            json_decode($serialized, true)
        );

        $this->assertDeserialize($serialized, Content::class, $content);
    }
}
