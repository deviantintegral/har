<?php

declare(strict_types=1);

namespace Deviantintegral\Har\SharedFields;

trait NameTrait
{
    /**
     * @var string
     * @Serializer\Type("string")
     */
    protected $name;

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
