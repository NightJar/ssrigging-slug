<?php

namespace Nightjar\Slug\Tests\Stubs;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;
use SilverStripe\Control\Controller;

class NewsPage extends DataObject implements TestOnly
{
    private static $has_many = [
        'Articles' => Article::class,
        'Journalists' => Journalist::class,
    ];

    public function Link($action = null)
    {
        return Controller::join_links('news/', $action);
    }
}
