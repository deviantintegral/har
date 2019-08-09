<?php

declare(strict_types=1);

namespace Deviantintegral\Har;

trait NameTrait
{
    /**
     * @var string
     * @Serializer\Type("string")
     */
    protected $name;

    /**
     * @param string $name
     *
     * @return self
     */
    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }
}
