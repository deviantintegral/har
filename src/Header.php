<?php

declare(strict_types=1);

namespace Deviantintegral\Har;

use Deviantintegral\Har\SharedFields\CommentTrait;
use Deviantintegral\Har\SharedFields\NameValueTrait;

/**
 * @see http://www.softwareishard.com/blog/har-12-spec/#headers
 */
final class Header
{
    use CommentTrait;
    use NameValueTrait;
}
