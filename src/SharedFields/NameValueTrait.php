<?php

declare(strict_types=1);

namespace Deviantintegral\Har\SharedFields;

use JMS\Serializer\Annotation as Serializer;

trait NameValueTrait
{
    use NameTrait;

    /**
     * The value.
     */
    #[Serializer\Type('string')]
    protected ?string $value = null;

    public function setValue(string $value): self
    {
        $this->value = $value;

        return $this;
    }

    public function getValue(): ?string
    {
        return $this->value;
    }
}
