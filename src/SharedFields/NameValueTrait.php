<?php

declare(strict_types=1);

namespace Deviantintegral\Har\SharedFields;

use JMS\Serializer\Annotation as Serializer;

trait NameValueTrait
{
    use NameTrait;

    /**
     * The value.
     *
     * @var string
     *
     * @Serializer\Type("string")
     */
    protected $value;

    public function setValue(string $value): self
    {
        $this->value = $value;

        return $this;
    }

    /**
     * @return string
     */
    public function getValue(): ?string
    {
        return $this->value;
    }
}
