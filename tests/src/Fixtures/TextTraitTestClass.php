<?php

declare(strict_types=1);

namespace Deviantintegral\Har\Tests\Fixtures;

use Deviantintegral\Har\SharedFields\TextTrait;

/**
 * Test fixture class that uses TextTrait directly without aliasing.
 *
 * This class is used to test the visibility of the trait's methods.
 * Unlike PostData and Content which alias setText as traitSetText
 * and override with their own public method, this class uses the
 * trait's setText directly.
 *
 * @internal Test fixture only
 */
final class TextTraitTestClass
{
    use TextTrait;
}
