<?php

namespace Nightjar\Slug\Tests\Stubs;

use SilverStripe\ORM\DataObject;
use SilverStripe\Dev\TestOnly;

class Lettuce extends DataObject implements TestOnly
{
    private static $many_many = [
        'Protection' => Blitzem::class
    ];
}
