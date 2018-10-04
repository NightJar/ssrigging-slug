<?php

namespace Nightjar\Slug\Tests\Stubs;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;
use Nightjar\Slug\Tests\Stubs\Lettuce;

class Blitzem extends DataObject implements TestOnly
{
    // extension applied in test set with params ("Title", "Protects")
    // as it errors on boot otherwise.

    private static $belongs_has_many = [
        'Protects' => Lettuce::class
    ];

    public function getTitle()
    {
        return 'No slugs!';
    }
}
