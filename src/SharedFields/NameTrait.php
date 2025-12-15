<?php

declare(strict_types=1);

namespace Deviantintegral\Har\SharedFields;

use JMS\Serializer\Annotation as Serializer;

trait NameTrait
{
    #[Serializer\Type('string')]
    protected string $name;

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }
}
