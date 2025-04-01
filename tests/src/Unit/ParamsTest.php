<?php

declare(strict_types=1);

namespace Deviantintegral\Har\Tests\Unit;

use Deviantintegral\Har\Params;

/**
 * @covers \Deviantintegral\Har\Params
 * @covers \Deviantintegral\Har\SharedFields\NameTrait
 * @covers \Deviantintegral\Har\SharedFields\NameValueTrait
 */
class ParamsTest extends HarTestBase
{
    public function testSerialize()
    {
        $params = (new Params())
          ->setName('Host')
          ->setValue('www.example.com')
          ->setFileName('example.md')
          ->setContentType('text/plain')
          ->setComment('Test value');

        $this->assertTrue($params->hasFileName());
        $this->assertNotNull($params->getFileName());
        $this->assertTrue($params->hasContentType());
        $this->assertNotNull($params->getContentType());

        $serializer = $this->getSerializer();
        $serialized = $serializer->serialize($params, 'json');
        $this->assertEquals(
            [
                'name' => 'Host',
                'value' => 'www.example.com',
                'fileName' => $params->getFileName(),
                'contentType' => $params->getContentType(),
                'comment' => 'Test value',
            ],
            json_decode($serialized, true)
        );

        $deserialized = $serializer->deserialize(
            $serialized,
            Params::class,
            'json'
        );
        $this->assertEquals($params, $deserialized);
    }

    public function testSerializeWithoutOptionalAttributes()
    {
        $params = (new Params())
            ->setName('Host')
            ->setValue('www.example.com')
            ->setComment('Test value');

        $this->assertFalse($params->hasFileName());
        $this->assertNull($params->getFileName());
        $this->assertFalse($params->hasContentType());
        $this->assertNull($params->getContentType());

        $serializer = $this->getSerializer();
        $serialized = $serializer->serialize($params, 'json');
        $this->assertEquals(
            [
                'name' => 'Host',
                'value' => 'www.example.com',
                'comment' => 'Test value',
            ],
            json_decode($serialized, true)
        );

        $deserialized = $serializer->deserialize(
            $serialized,
            Params::class,
            'json'
        );
        $this->assertEquals($params, $deserialized);
    }
}
