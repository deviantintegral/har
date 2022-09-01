<?php

declare(strict_types=1);

namespace Deviantintegral\Har;

use Deviantintegral\Har\SharedFields\CommentTrait;
use Deviantintegral\Har\SharedFields\MimeTypeTrait;
use Deviantintegral\Har\SharedFields\TextTrait;

use function GuzzleHttp\Psr7\build_query;

use JMS\Serializer\Annotation as Serializer;

/**
 * @see http://www.softwareishard.com/blog/har-12-spec/#postData
 */
final class PostData
{
    use CommentTrait;
    use MimeTypeTrait;
    use TextTrait {
        setText as traitSetText;
    }

    /**
     * List of posted parameters (in case of URL encoded parameters).
     *
     * @var \Deviantintegral\Har\Params[]
     * @Serializer\Type("array<Deviantintegral\Har\Params>")
     */
    private $params;

    /**
     * @return \Deviantintegral\Har\Params[]
     */
    public function getParams(): array
    {
        $this->traitSetText();
        if (empty($this->params)) {
            return [];
        }

        return $this->params;
    }

    /**
     * @param \Deviantintegral\Har\Params[] $params
     */
    public function setParams(array $params): self
    {
        $this->params = $params;
        // Text and params are mutually exclusive.
        $this->text = null;

        return $this;
    }

    public function setText(string $text): self
    {
        $this->traitSetText($text);
        // Text and params are mutually exclusive.
        $this->params = [];

        return $this;
    }

    public function hasParams(): bool
    {
        return !empty($this->params);
    }

    public function getBodySize(): int
    {
        if ($this->hasParams()) {
            $query = [];
            foreach ($this->params as $param) {
                $query[$param->getName()] = $param->getValue();
            }
            $string = build_query($query);

            return \strlen($string);
        }

        if ($this->hasText()) {
            return \strlen($this->getText());
        }

        return 0;
    }
}
