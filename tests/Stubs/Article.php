<?php

namespace Nightjar\Slug\Tests\Stubs;

use SilverStripe\ORM\DataObject;
use SilverStripe\Dev\TestOnly;

class Article extends DataObject implements TestOnly
{
    private static $extensions = [
        'Slug("Title", "Parent", true)'
    ];

    private static $db = [
        'Title' => 'Varchar'
    ];

    private static $has_one = [
        'Parent' => NewsPage::class
    ];
}
