<?php

namespace Nightjar\Slug\Tests\Stubs;

use Nightjar\Slug\Slug;
use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;

class Journalist extends DataObject implements TestOnly
{
    // Slug extension applied in test setUp, so we can test different instantiation methods.

    private static $db = [
        'Name' => 'Varchar'
    ];

    private static $has_one = [
        'NewsPages' => NewsPage::class
    ];

    private static $extensions = [
        Slug::class . '("Name", "NewsPages")'
    ];
}
