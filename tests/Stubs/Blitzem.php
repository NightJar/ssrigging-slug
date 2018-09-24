<?php

namespace Nightjar\Slug\Tests\Stubs;

use SilverStripe\ORM\DataObject;
use SilverStripe\Dev\TestOnly;

class Blitzem extends DataObject implements TestOnly
{
    private static $has_many = [
        'Protects' => Lettuce::class
    ];

    public function getTitle()
    {
        return 'No slugs!';
    }
}
