<?php

declare(strict_types=1);

namespace Deviantintegral\Har\SharedFields;

use Deviantintegral\Har\Content;

trait TextTrait
{
    /**
     * Response body sent from the server or loaded from the browser cache. This
     * field is populated with textual content only. The text field is either
     * HTTP decoded text or a encoded (e.g. "base64") representation of the
     * response body. Leave out this field if the information is not available.
     *
     * @var string
     * @Serializer\Type("string")
     */
    protected $text;

    /**
     * @param string $text
     *
     * @return Content
     */
    public function setText(string $text): Content
    {
        $this->text = $text;

        return $this;
    }

    /**
     * @return bool
     */
    public function hasText(): bool
    {
        return null === $this->text;
    }

    /**
     * @return string
     */
    public function getText(): string
    {
        return $this->text;
    }
}
