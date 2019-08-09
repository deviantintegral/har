<?php

declare(strict_types=1);

namespace Deviantintegral\Har;

/**
 * Represents the root HTTP Archive node.
 *
 * @see http://www.softwareishard.com/blog/har-12-spec/#log
 */
class Log
{
    use CommentTrait;

    /**
     * Support the finest \DateTime precision we can.
     */
    public const ISO_8601_MICROSECONDS = 'Y-m-d\TH:i:s.uO';

    /**
     * @var string
     */
    private $version;

    private $creator;
    private $browser;
    private $pages;
    private $entries;
}
