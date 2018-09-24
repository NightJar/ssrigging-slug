<?php

namespace Nightjar\Slug\Tests\Stubs;

use SilverStripe\ORM\DataObject;
use SilverStripe\Dev\TestOnly;

class Journalist extends DataObject implements TestOnly
{
    private static $db = [
        'Name' => 'Varchar'
    ];

    private static $belongs_many_many = [
        'NewsPages'
    ];
}
