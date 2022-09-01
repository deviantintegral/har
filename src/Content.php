<?php

declare(strict_types=1);

namespace Deviantintegral\Har;

use Deviantintegral\Har\SharedFields\MimeTypeTrait;
use Deviantintegral\Har\SharedFields\TextTrait;
use JMS\Serializer\Annotation as Serializer;

final class Content
{
    use MimeTypeTrait;
    use TextTrait {
        setText as traitSetText;
    }

    /**
     * Length of the returned content in bytes. Should be equal to
     * response.bodySize if there is no compression and bigger when the content
     * has been compressed.
     *
     * @var int
     * @Serializer\Type("integer")
     */
    private $size;

    /**
     * compression [number, optional] - Number of bytes saved. Leave out this
     * field if the information is not available.
     *
     * @var int
     * @Serializer\Type("integer")
     */
    private $compression;

    /**
     * Number of bytes saved. Leave out this field if the information is not
     * available.
     *
     * @var int
     * @Serializer\Type("integer")
     */
    private $number;

    /**
     * Encoding used for response text field e.g "base64". Leave out this field
     * if the text field is HTTP decoded (decompressed & unchunked), than
     * trans-coded from its original character set into UTF-8.
     *
     * @var string
     * @Serializer\Type("string")
     */
    private $encoding;

    public function getCompression(): int
    {
        return $this->compression;
    }

    public function setCompression(int $compression): self
    {
        $this->compression = $compression;

        return $this;
    }

    public function getSize(): int
    {
        return $this->size;
    }

    public function setSize(int $size): self
    {
        $this->size = $size;

        return $this;
    }

    public function setText(string $text): self
    {
        $this->traitSetText($text);
        $this->setSize(\strlen($text));

        return $this;
    }

    public function hasNumber(): bool
    {
        return null !== $this->number;
    }

    public function getNumber(): ?int
    {
        return $this->number;
    }

    public function setNumber(int $number): self
    {
        $this->number = $number;

        return $this;
    }

    public function hasEncoding(): bool
    {
        return null !== $this->encoding;
    }

    public function getEncoding(): ?string
    {
        return $this->encoding;
    }

    public function setEncoding(string $encoding): self
    {
        $this->encoding = $encoding;

        return $this;
    }
}
