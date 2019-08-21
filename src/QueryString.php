<?php

declare(strict_types=1);

namespace Deviantintegral\Har;

use Deviantintegral\Har\SharedFields\CommentTrait;
use Deviantintegral\Har\SharedFields\NameValueTrait;

/**
 * @see http://www.softwareishard.com/blog/har-12-spec/#queryString
 */
final class QueryString
{
    use CommentTrait;
    use NameValueTrait;
}
