<?php

namespace Nightjar\Slug\Tests\Stubs;

use SilverStripe\ORM\DataObject;
use SilverStripe\Dev\TestOnly;

class NewsPage extends DataObject implements TestOnly
{
    private static $has_many = [
        'Articles' => Article::class,
    ];

    private static $many_many = [
        'Journalists' => Journalist::class
    ];

    public function Link($action = null)
    {
        return Controller::join_links('news/', $action);
    }
}