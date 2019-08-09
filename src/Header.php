<?php

declare(strict_types=1);

namespace Deviantintegral\Har;

/**
 * @see http://www.softwareishard.com/blog/har-12-spec/#headers
 */
final class Header
{
    use CommentTrait;
    use NameValueTrait;
}
